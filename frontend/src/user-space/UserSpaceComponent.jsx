import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/useConfig'

// import app components
import NavbarElement from './elements/NavbarElement'
import Loading from '../sub-components/LoadingComponent'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent'
import ChatListComponent from './chat/ChatListComponent'
import ChatComponent from './chat/ChatComponent'

export default function UserSpaceComponent() {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(true)
    const [userData, setUserData] = useState(null)
    const [selectedChat, setSelectedChat] = useState(null)
    const [chatList, setChatList] = useState([])
    const [chatListLoading, setChatListLoading] = useState(true)
    const [chatListError, setChatListError] = useState(null)
    const [messages, setMessages] = useState([])
    const [messagesLoading, setMessagesLoading] = useState(false)
    const [messagesError, setMessagesError] = useState(null)
    const [hasMoreMessages, setHasMoreMessages] = useState(true)
    const [isFetchingMoreMessages, setIsFetchingMoreMessages] = useState(false)
    const [currentOldestMessageOffset, setCurrentOldestMessageOffset] = useState(0)
    const [totalMessagesCount, setTotalMessagesCount] = useState(0) // This will now be determined on frontend
    
    // fetch user data
    useEffect(() => {
        const fetchUserData = async () => {
            try {
                const res = await fetch(`${API_URL}/api/user/info`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get response data
                const data = await res.json()

                // check if response was successful
                if (res.ok && data.status === 'success') {
                    setUserData(data.data)
                } else {
                    setError(data.message || 'Failed to fetch user info')
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error fetching user info: ' + err.message)
                setError('Error fetching user info.')
            } finally {
                setLoading(false)
            }
        }

        fetchUserData()
    }, [DEV_MODE, API_TOKEN, API_URL])

    // fetch chat list
    useEffect(() => {
        const fetchChatList = async () => {
            try {
                const res = await fetch(`${API_URL}/api/chat/chat-list`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN,
                    }
                })

                // get response data
                const data = await res.json()

                // check if response was successful
                if (res.ok && data.status === 'success') {
                    const chatsWithLastMessage = await Promise.all(data.data.map(async (chat) => {
                        const historyRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=1`, {
                            credentials: 'include',
                            headers: {
                                'Accept': 'application/json',
                                'X-API-TOKEN': API_TOKEN,
                            }
                        });
                        const historyData = await historyRes.json();
                        if (historyData.status === 'success' && historyData.data.length > 0) {
                            chat.lastMessage = historyData.data[0];
                        }
                        return chat;
                    }));
                    setChatList(chatsWithLastMessage)
                } else {
                    setChatListError(data.message || 'Failed to fetch chat list')
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error fetching chat list: ' + err.message)
                setChatListError('Error fetching chat list.')
            } finally {
                setChatListLoading(false)
            }
        }

        fetchChatList()
    }, [DEV_MODE, API_TOKEN, API_URL])

    // fetch chat history
    const fetchChatHistory = async (offset, limit = 100, prepend = false, showLoadingIndicator = true) => {
        if (!selectedChat || isFetchingMoreMessages) return

        if (!prepend && showLoadingIndicator) { // Initial load or refresh with indicator
            setMessagesLoading(true)
            setMessagesError(null)
            setHasMoreMessages(true)
            setMessages([]) // Clear messages on new chat selection
            setIsFetchingMoreMessages(false) // Ensure this is false for initial/refresh loads
        } else if (prepend) { // Loading more messages (prepended)
            setIsFetchingMoreMessages(true)
        }

        try {
            let url = `${API_URL}/api/chat/history?chat_id=${selectedChat['chat-id']}&limit=${limit}&offset=${offset}`
            let logMessage = `Fetching chat history for chat_id: ${selectedChat['chat-id']}, limit: ${limit}, offset: ${offset}`
            if (DEV_MODE) console.log(logMessage)

            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                }
            })

            // get response data
            const data = await res.json()

            // check if response was successful
            if (res.ok && data.status === 'success') {
                if (DEV_MODE) {
                    console.log(`Received ${data.data.length} messages. IDs: ${data.data.length > 0 ? `${data.data[0].id} - ${data.data[data.data.length - 1].id}` : 'N/A'}`)
                }
                if (data.data.length < limit) {
                    setHasMoreMessages(false)
                }
                if (prepend) {
                    setMessages(prevMessages => {
                        const existingMessageIds = new Set(prevMessages.map(msg => msg.id));
                        const newUniqueMessages = data.data.filter(msg => !existingMessageIds.has(msg.id));
                        const newMessages = [...newUniqueMessages, ...prevMessages];
                        if (DEV_MODE) console.log(`[UserSpaceComponent] Total messages on page: ${newMessages.length}`);
                        return newMessages;
                    });
                } else {
                    const newMessages = data.data;
                    setMessages(newMessages);
                    if (DEV_MODE) console.log(`[UserSpaceComponent] Total messages on page: ${newMessages.length}`);
                }
                setCurrentOldestMessageOffset(offset)
            } else {
                setMessagesError(data.message || 'Failed to fetch chat history')
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error fetching chat history: ' + err.message)
            setMessagesError('Error fetching chat history.')
        } finally {
            if (!prepend && showLoadingIndicator) {
                setMessagesLoading(false)
            } else if (prepend) {
                setIsFetchingMoreMessages(false)
            }
        }
    }

    useEffect(() => {
        const fetchInitialChatHistory = async () => {
            if (!selectedChat) {
                setMessages([])
                return
            }

            setMessagesLoading(true)
            setMessagesError(null)
            setHasMoreMessages(true)
            setTotalMessagesCount(0)

            // Workaround: Fetch all messages to get total count, then paginate.
            const allMessagesRes = await fetch(`${API_URL}/api/chat/history?chat_id=${selectedChat['chat-id']}&limit=1000000&offset=0`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                }
            });
            const allMessagesData = await allMessagesRes.json();

            if (allMessagesRes.ok && allMessagesData.status === 'success') {
                const allMessages = allMessagesData.data;
                const count = allMessages.length;
                setTotalMessagesCount(count);

                const initialLimit = 100;
                const initialOffset = Math.max(0, count - initialLimit);
                
                // Take the last 'initialLimit' messages from the fetched 'allMessages'
                const messagesToDisplay = allMessages.slice(initialOffset);
                setMessages(messagesToDisplay);
                setCurrentOldestMessageOffset(initialOffset);
                setMessagesLoading(false);
                if (DEV_MODE) console.log(`[UserSpaceComponent] Initial messages displayed: ${messagesToDisplay.length}. Total count: ${count}`);

                if (initialOffset === 0) {
                    setHasMoreMessages(false);
                }

            } else {
                console.error('Failed to fetch all messages for count', allMessagesData.message);
                setMessagesError(allMessagesData.message);
                setMessagesLoading(false);
            }
        }

        fetchInitialChatHistory()
    }, [selectedChat, DEV_MODE, API_TOKEN, API_URL])

    // show loading screen
    if (loading) return <Loading/>

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    return (
        <div className="h-screen flex flex-col bg-gray-800">
            {/* nav-bar */}
            <NavbarElement userData={userData}/>

            {/* chat layout */}
            <div className="flex flex-grow overflow-hidden">
                <div className={`w-full md:w-1/3 ${selectedChat ? 'hidden' : 'flex'} md:flex flex-col`}>
                    <ChatListComponent chats={chatList} onSelectChat={setSelectedChat} loading={chatListLoading} error={chatListError} currentUser={userData} />
                </div>
                <div className={`w-full md:w-2/3 ${selectedChat ? 'flex' : 'hidden'} md:flex flex-col`}>
                    <ChatComponent 
                        chat={selectedChat} 
                        messages={messages} 
                        messagesLoading={messagesLoading} 
                        messagesError={messagesError} 
                        currentUser={userData} 
                        onBack={() => setSelectedChat(null)} 
                        onMessageSent={() => {
                            setTotalMessagesCount(prevCount => prevCount + 1);
                            const newInitialOffset = Math.max(0, totalMessagesCount + 1 - 100); // +1 for the new message
                            fetchChatHistory(newInitialOffset, 100, false, false); // showLoadingIndicator = false
                        }} 
                        onLoadMoreMessages={() => {
                            const newOffset = Math.max(0, currentOldestMessageOffset - 100);
                            fetchChatHistory(newOffset, 100, true);
                        }} 
                        hasMoreMessages={hasMoreMessages && currentOldestMessageOffset > 0}
                        isFetchingMoreMessages={isFetchingMoreMessages}
                        API_URL={API_URL} 
                        API_TOKEN={API_TOKEN} 
                    />
                </div>
            </div>
        </div>
    )
}
