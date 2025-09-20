import React from 'react';
import defaultProfileImage from '../../assets/images/default-profile-image.png';

const ChatListComponent = ({ chats, onSelectChat, loading, error, currentUser }) => {
    if (loading) {
        return <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full p-3 text-white">Loading chats...</div>;
    }

    if (error) {
        return <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full p-3 text-red-500">{error}</div>;
    }

    return (
        <div className="bg-gray-800 border-r border-gray-700 overflow-y-auto h-full">
            {chats.map(chat => (
                <div key={chat['chat-id']} className="flex items-center p-3 cursor-pointer border-b border-gray-700 hover:bg-gray-700" onClick={() => onSelectChat(chat)}>
                    <img 
                        src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                        alt={chat.username} 
                        className="w-12 h-12 rounded-full mr-4" 
                    />
                    <div className="flex-1">
                        <div className="font-bold text-white">{chat.username}</div>
                        <div className="text-gray-400 text-sm">
                            {chat.lastMessage && currentUser && (
                                <>
                                    {chat.lastMessage.sender === currentUser['user-id'] ? 'You: ' : ''}
                                    {chat.lastMessage.message}
                                </>
                            )}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default ChatListComponent;
