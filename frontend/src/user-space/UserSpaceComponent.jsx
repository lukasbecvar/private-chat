import { useEffect, useState, useCallback, useRef } from 'react'
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
    const CHAT_LIMIT = getConfigValue('CHAT_LIMIT')
    const CHAT_POLLING_TIME = getConfigValue('CHAT_POLLING_TIME')

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
    const isPollingRef = useRef(false)
    
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
    const fetchChatHistory = async (offset, limit = CHAT_LIMIT, prepend = false, showLoadingIndicator = true) => {
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
                        const newMessages = [...newUniqueMessages.reverse(), ...prevMessages];
                        if (DEV_MODE) console.log(`[UserSpaceComponent] Total messages on page: ${newMessages.length}`);
                        return newMessages;
                    });
                } else {
                    const newMessages = data.data.reverse();
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

                const initialLimit = CHAT_LIMIT;
                const initialOffset = Math.max(0, count - initialLimit);
                
                // Take the last 'initialLimit' messages from the fetched 'allMessages'
                const messagesToDisplay = allMessages.slice(initialOffset).reverse();
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
    }, [selectedChat, DEV_MODE, API_TOKEN, API_URL, CHAT_LIMIT])

    const handleSendMessage = async (message) => {
        try {
            const res = await fetch(`${API_URL}/api/chat/send`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                },
                body: JSON.stringify({
                    message: message,
                    receiver_id: selectedChat['user-id'],
                }),
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                // Immediately poll for new messages to get the one we just sent.
                await pollNewMessages();
                return true; // Indicate success
            } else {
                console.error('Failed to send message:', data.message);
                return false; // Indicate failure
            }
        } catch (error) {
            console.error('Error sending message:', error);
            return false; // Indicate failure
        }
    };

    const pollNewMessages = useCallback(async () => {
        if (!selectedChat || isPollingRef.current) {
            return;
        }
        isPollingRef.current = true;

        const lastPermanentMessage = [...messages].reverse().find(msg => msg.id);
        const lastMessageId = lastPermanentMessage ? lastPermanentMessage.id : 0;

        try {
            const res = await fetch(`${API_URL}/api/chat/history?chat_id=${selectedChat['chat-id']}&after_id=${lastMessageId}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                }
            });

            const data = await res.json();

            if (res.ok && data.status === 'success' && data.data.length > 0) {
                const newMessages = data.data.reverse();
                setMessages(prevMessages => {
                    const existingMessageIds = new Set(prevMessages.map(msg => msg.id));
                    const uniqueNewMessages = newMessages.filter(msg => !existingMessageIds.has(msg.id));
                    if (uniqueNewMessages.length > 0) {
                        return [...prevMessages, ...uniqueNewMessages];
                    }
                    return prevMessages;
                });
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error polling for new messages: ' + err.message);
        } finally {
            isPollingRef.current = false;
        }
    }, [selectedChat, messages, API_URL, API_TOKEN, DEV_MODE]);

    useEffect(() => {
        if (!selectedChat) {
            return;
        }

        const intervalId = setInterval(pollNewMessages, CHAT_POLLING_TIME);

        return () => clearInterval(intervalId);
    }, [selectedChat, pollNewMessages, CHAT_POLLING_TIME]);

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
                        onSendMessage={handleSendMessage} 
                        onLoadMoreMessages={() => {
                            const newOffset = Math.max(0, currentOldestMessageOffset - CHAT_LIMIT);
                            fetchChatHistory(newOffset, CHAT_LIMIT, true);
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