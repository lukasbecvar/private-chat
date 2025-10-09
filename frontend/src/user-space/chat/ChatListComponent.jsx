import { useState, useEffect, useCallback } from 'react'
import { useConfig } from '../../hooks/useConfig'

// import sub-components
import BouncingLoader from '../../sub-components/BouncingLoader'
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent'

// import app components
import defaultProfileImage from '../../assets/images/default-profile-image.png'

export default function ChatListComponent({ onSelectChat, currentUser, onChatListLoad, selectedChat, onChatListLoaded }) {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')
    const CHAT_POLLING_TIME_MS = getConfigValue('CHAT_POLLING_TIME_MS')

    // states
    const [chats, setChats] = useState([])
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')

    // fetch chat list from API
    const fetchChatList = useCallback(async (isPolling = false) => {
        // enable loading when chat list is fetching
        if (!isPolling) {
            setLoading(true)
        }
        try {
            const res = await fetch(`${API_URL}/api/chat/chat-list`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                }
            })

            // get response data
            const data = await res.json()

            // return if server is unavailable
            if (res.status === 503) {
                return
            }

            // check if chat list was fetched successfully
            if (res.ok && data.status === 'success') {
                setError(null)
                const transformedChats = data.data.map(chat => ({
                    ...chat,
                    lastMessage: chat['last-message']
                }))
                setChats(transformedChats)
                if (onChatListLoad) {
                    onChatListLoad(transformedChats)
                }
            } else {
                if (DEV_MODE) console.error(`Error fetching chat list: ${data.message}`)
                setError(data.message || 'Failed to fetch chat list')
            }
        } catch (err) {
            if (DEV_MODE) console.error(`Error fetching chat list: ${err.message}`)
            setError('Error fetching chat list.')
        } finally {
            if (!isPolling) {
                setLoading(false)
                if (onChatListLoaded) {
                    onChatListLoaded()
                }
            }
        }
    }, [API_URL, API_TOKEN, DEV_MODE, onChatListLoad, onChatListLoaded])

    // initial call to fetch chat list & set up polling
    useEffect(() => {
        // initial fetch
        fetchChatList(false)

        // set up interval for polling
        const intervalId = setInterval(() => fetchChatList(true), CHAT_POLLING_TIME_MS)

        // clean up interval on component unmount
        return () => clearInterval(intervalId)
    }, [fetchChatList, CHAT_POLLING_TIME_MS])

    // filter chats based on search term
    const filteredChats = chats.filter(chat =>
        chat.username.toLowerCase().includes(searchTerm.toLowerCase())
    )

    // show loading when chat list is fetching
    if (loading && onChatListLoaded) {
        return (
            <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full flex items-center justify-center">
                <BouncingLoader/>
            </div>
        )
    }

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    // render chat list
    return (
        <div className="bg-gray-800/70 border-r border-gray-700/50 overflow-y-auto h-full" id="chat-list">
            {/* search bar */}
            <div className="p-[2px] border-b border-gray-700/50">
                <input
                    type="text"
                    value={searchTerm}
                    placeholder="Search username..."
                    onChange={e => setSearchTerm(e.target.value)}
                    className="w-full p-2 bg-gray-700/60 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-colors duration-300"
                />
            </div>

            {/* chat list */}
            {filteredChats.map(chat => (
                <div
                    key={chat['chat-id']}
                    onClick={() => onSelectChat(chat)}
                    className={`flex items-center p-3 cursor-pointer border-b border-gray-700/50 transition-colors duration-200 ${selectedChat && selectedChat['chat-id'] === chat['chat-id'] ? 'bg-blue-600/50' : 'hover:bg-gray-700/50'}`}>
                    <img
                        alt={chat.username}
                        className="w-12 h-12 rounded-full mr-4 flex-shrink-0"
                        src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                    />
                    <div className="flex-1 min-w-0">
                        <div className={`font-bold truncate ${chat.roles.some(role => role.includes('ROLE_ADMIN')) ? 'text-red-400' : 'text-white'}`}>{chat.username}</div>
                        {chat.lastMessage && currentUser && (
                            <div className="text-gray-400 text-sm truncate">
                                <span className="font-semibold">
                                    {chat.lastMessage['sender-name'] === currentUser.username ? 'You' : chat.lastMessage['sender-name']}
                                </span>
                                {chat.lastMessage.message && <span>: {chat.lastMessage.message}</span>}
                            </div>
                        )}
                    </div>
                </div>
            ))}
        </div>
    )
}
