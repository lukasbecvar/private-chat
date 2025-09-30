import { useState, useEffect, useCallback } from 'react'
import { useConfig } from '../../hooks/useConfig'

// import sub-components
import BouncingLoader from '../../sub-components/BouncingLoader'
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent'

// import app components
import defaultProfileImage from '../../assets/images/default-profile-image.png'

export default function ChatListComponent({ onSelectChat, currentUser, onChatListLoad, selectedChat }) {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [chats, setChats] = useState([])
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')

    // fetch chat list from API
    const fetchChatList = useCallback(async () => {
        try {
            const res = await fetch(`${API_URL}/api/chat/chat-list`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                }
            })

            const data = await res.json()

            if (res.ok && data.status === 'success') {
                const transformedChats = data.data.map(chat => ({
                    ...chat,
                    lastMessage: chat['last-message']
                }))
                setChats(transformedChats)
                if (onChatListLoad) {
                    onChatListLoad(transformedChats)
                }
            } else {
                if (DEV_MODE) console.error('Error fetching chat list: ' + data.message)
                setError(data.message || 'Failed to fetch chat list')
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error fetching chat list: ' + err.message)
            setError('Error fetching chat list.')
        } finally {
            setLoading(false)
        }
    }, [API_URL, API_TOKEN, DEV_MODE, onChatListLoad])

    // initial call to fetch chat list
    useEffect(() => {
        fetchChatList()
    }, [fetchChatList])

    // show loading screen
    if (loading) {
        return <BouncingLoader />
    }

    // show error screen
    if (error) {
        return <ErrorScreenComponent errorMessage={error} />
    }

    // filter chats based on search term
    const filteredChats = chats.filter(chat =>
        chat.username.toLowerCase().includes(searchTerm.toLowerCase())
    )

    // render chat list
    return (
        <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full">
            {/* search bar */}
            <div className="p-0.5 border-b border-gray-700">
                <input
                    type="text"
                    value={searchTerm}
                    placeholder="Search username..."
                    onChange={e => setSearchTerm(e.target.value)}
                    className="w-full p-2 bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            {/* chat list */}
            {filteredChats.map(chat => (
                <div
                    key={chat['chat-id']}
                    onClick={() => onSelectChat(chat)}
                    className={`flex items-center p-3 cursor-pointer border-b border-gray-700 ${selectedChat && selectedChat['chat-id'] === chat['chat-id'] ? 'bg-gray-700' : 'hover:bg-gray-700'}`}
                >
                    <img
                        alt={chat.username}
                        className="w-12 h-12 rounded-full mr-4 flex-shrink-0 border border-gray-400/50"
                        src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                    />
                    <div className="flex-1 min-w-0">
                        <div className={`font-bold truncate ${chat.roles.some(role => role.includes('ROLE_ADMIN')) ? 'text-red-400' : 'text-white'}`}>{chat.username}</div>
                        {chat.lastMessage && currentUser && (
                            <div className="text-gray-400 text-sm truncate">
                                <span className="font-semibold">
                                    {chat.lastMessage['sender-name'] === currentUser.username ? 'You' : chat.lastMessage['sender-name']}
                                </span>
                                : {chat.lastMessage.message}
                            </div>
                        )}
                    </div>
                </div>
            ))}
        </div>
    )
}
