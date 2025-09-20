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
    const fetchChatHistory = async () => {
        if (!selectedChat) return

        setMessagesLoading(true)
        setMessagesError(null)
        try {
            const res = await fetch(`${API_URL}/api/chat/history?chat_id=${selectedChat['chat-id']}`, {
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
                setMessages(data.data)
            } else {
                setMessagesError(data.message || 'Failed to fetch chat history')
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error fetching chat history: ' + err.message)
            setMessagesError('Error fetching chat history.')
        } finally {
            setMessagesLoading(false)
        }
    }

    useEffect(() => {
        fetchChatHistory()
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
                        onMessageSent={fetchChatHistory} 
                        API_URL={API_URL} 
                        API_TOKEN={API_TOKEN} 
                    />
                </div>
            </div>
        </div>
    )
}
