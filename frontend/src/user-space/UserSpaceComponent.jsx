import { useCallback, useEffect, useState } from 'react'
import { useConfig } from '../hooks/useConfig'

// import sub-components
import Loading from '../sub-components/LoadingComponent'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent'

// import app components
import ChatComponent from './chat/ChatComponent'
import NavbarElement from './elements/NavbarElement'
import ChatListComponent from './chat/ChatListComponent'

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
    const [chatLoading, setChatLoading] = useState(false)
    const [selectedChat, setSelectedChat] = useState(null)
    const [isChatListLoading, setChatListLoading] = useState(true)

    // handle chat selection
    const handleSelectChat = useCallback((chat) => {
        setChatLoading(true)
        setSelectedChat(chat)
    }, [])

    // handle chat loaded
    const onChatLoaded = useCallback(() => {
        setChatLoading(false)
    }, [])

    // handle chat list loaded
    const onChatListLoaded = useCallback(() => {
        setChatListLoading(false)
    }, [])

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

    // handle chat list load
    const handleChatListLoad = useCallback((chats) => {
        const isDesktop = window.innerWidth >= 768;

        setSelectedChat(prevSelectedChat => {
            const newVersionOfSelectedChat = prevSelectedChat ? chats.find(c => c['chat-id'] === prevSelectedChat['chat-id']) : null;

            // return it to preserve selection
            if (newVersionOfSelectedChat) {
                return newVersionOfSelectedChat;
            }

            // select first chat automatically when chat list loads
            if (isDesktop && chats.length > 0) {
                return chats[0];
            }

            // return null (chat not being selected)
            return null;
        });
    }, [])

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
                <div className={`w-full md:w-1/4 ${selectedChat ? 'hidden' : 'flex'} md:flex flex-col`} data-testid="chat-list">
                    <ChatListComponent onSelectChat={handleSelectChat} currentUser={userData} onChatListLoad={handleChatListLoad} selectedChat={selectedChat} onChatListLoaded={onChatListLoaded}/>
                </div>
                <div className={`w-full md:w-3/4 ${selectedChat || isChatListLoading ? 'flex' : 'hidden'} md:flex flex-col items-center justify-center`} data-testid="chat-component">
                    {isChatListLoading ? (
                        <Loading/>
                    ) : (
                        <>
                            {chatLoading && <Loading/>}
                            <div style={{ display: chatLoading ? 'none' : 'block', height: '100%', width: '100%' }}>
                                <ChatComponent
                                    chat={selectedChat}
                                    onBack={() => setSelectedChat(null)}
                                    onChatLoaded={onChatLoaded}
                                />
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    )
}
