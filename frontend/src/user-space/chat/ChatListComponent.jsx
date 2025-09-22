import React, { useState, useEffect } from 'react';
import defaultProfileImage from '../../assets/images/default-profile-image.png';
import { useConfig } from '../../hooks/useConfig';

const ChatListComponent = ({ onSelectChat, currentUser, onChatListLoad }) => {
    const { getConfigValue } = useConfig();
    const API_URL = getConfigValue('API_URL');
    const DEV_MODE = getConfigValue('DEV_MODE');
    const API_TOKEN = getConfigValue('API_TOKEN');

    const [chats, setChats] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchChatList = async () => {
            try {
                const res = await fetch(`${API_URL}/api/chat/chat-list`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN,
                    }
                });

                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    const transformedChats = data.data.map(chat => ({
                        ...chat,
                        lastMessage: chat['last-message']
                    }));
                    setChats(transformedChats);
                    if (onChatListLoad) {
                        onChatListLoad(transformedChats);
                    }
                } else {
                    setError(data.message || 'Failed to fetch chat list');
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error fetching chat list: ' + err.message);
                setError('Error fetching chat list.');
            } finally {
                setLoading(false);
            }
        };

        fetchChatList();
    }, [DEV_MODE, API_TOKEN, API_URL]);

    if (loading) {
        return <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full p-3 text-white">Loading chats...</div>;
    }

    if (error) {
        return <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full p-3 text-red-500">{error}</div>;
    }

    return (
        <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full">
            {chats.map(chat => (
                <div
                    key={chat['chat-id']}
                    className="flex items-center p-3 cursor-pointer border-b border-gray-700 hover:bg-gray-700"
                    onClick={() => onSelectChat(chat)}
                >
                    <img
                        src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                        alt={chat.username}
                        className="w-12 h-12 rounded-full mr-4 flex-shrink-0"
                    />
                    <div className="flex-1 min-w-0">
                        <div className="font-bold text-white truncate">{chat.username}</div>
                        {chat.lastMessage && chat.lastMessage['sender-name'] && chat.lastMessage.message && currentUser && (
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
    );
};

export default ChatListComponent;
