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

    // states
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(true)
    const [userData, setUserData] = useState(null)
    const [selectedChat, setSelectedChat] = useState(null)
    const [chatList, setChatList] = useState([])
    
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

    // show loading screen
    if (loading) return <Loading/>

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    const handleChatListLoad = (chats) => {
        setChatList(chats);
        if (chats.length > 0) {
            setSelectedChat(chats[0]);
        }
    };

    return (
        <div className="h-screen flex flex-col bg-gray-800">
            {/* nav-bar */}
            <NavbarElement userData={userData}/>

            {/* chat layout */}
            <div className="flex flex-grow overflow-hidden">
                <div className={`w-full md:w-1/4 ${selectedChat ? 'hidden' : 'flex'} md:flex flex-col`}>
                    <ChatListComponent onSelectChat={setSelectedChat} currentUser={userData} onChatListLoad={handleChatListLoad} />
                </div>
                <div className={`w-full md:w-2/2 ${selectedChat ? 'flex' : 'hidden'} md:flex flex-col`}>
                    <ChatComponent 
                        chat={selectedChat} 
                        currentUser={userData} 
                        onBack={() => setSelectedChat(null)} 
                    />
                </div>
            </div>
        </div>
    )
}