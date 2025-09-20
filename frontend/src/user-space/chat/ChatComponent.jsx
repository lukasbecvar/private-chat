import React, { useState, useEffect, useRef } from 'react';

const ChatComponent = ({ chat, messages, messagesLoading, messagesError, onBack, onMessageSent, API_URL, API_TOKEN }) => {
    const [newMessage, setNewMessage] = useState('');
    const [lastMessageId, setLastMessageId] = useState(null);
    const chatContainerRef = useRef(null);
    const [shouldScrollToBottom, setShouldScrollToBottom] = useState(true);

    const handleScroll = () => {
        if (chatContainerRef.current) {
            const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current;
            // If the user is within 100px of the bottom, enable auto-scroll
            if (scrollHeight - scrollTop <= clientHeight + 100) {
                setShouldScrollToBottom(true);
            } else {
                setShouldScrollToBottom(false);
            }
        }
    };

    // Effect to set up autofetch
    useEffect(() => {
        if (!chat) return;

        // Initialize lastMessageId with the ID of the last message if available
        if (messages && messages.length > 0) {
            setLastMessageId(messages[messages.length - 1].id);
        } else {
            setLastMessageId(null);
        }

        const fetchNewMessages = async () => {
            try {
                const url = lastMessageId ? `${API_URL}/api/chat/history?after_id=${lastMessageId}&chat_id=${chat['chat-id']}` : `${API_URL}/api/chat/history?chat_id=${chat['chat-id']}`;
                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'X-API-TOKEN': API_TOKEN,
                    },
                });
                const data = await res.json();

                if (data.status === 'success' && data.data.length > 0) {
                    console.log('New messages:', data.data);
                    // Update lastMessageId with the ID of the latest new message
                    setLastMessageId(data.data[data.data.length - 1].id);
                    onMessageSent(); // Trigger a refresh of messages in the parent component
                }
            } catch (error) {
                console.error('Error fetching new messages:', error);
            }
        };

        const intervalId = setInterval(fetchNewMessages, 1000); // Fetch every 1 second

        return () => clearInterval(intervalId); // Cleanup on unmount or chat change
    }, [chat, lastMessageId, API_URL, API_TOKEN, onMessageSent, messages]);

    // Effect to scroll to bottom when messages change, if shouldScrollToBottom is true
    useEffect(() => {
        if (chatContainerRef.current && shouldScrollToBottom) {
            chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
        }
    }, [messages, shouldScrollToBottom]);

    const handleSendMessage = async () => {
        if (newMessage.trim() === '') return;

        try {
            const res = await fetch(`${API_URL}/api/chat/send`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                },
                body: JSON.stringify({
                    message: newMessage,
                    receiver_id: chat['user-id'],
                }),
            });

            const data = await res.json();

            if (data.status === 'success') {
                setNewMessage('');
                onMessageSent(); // Callback to refresh messages
            } else {
                // Handle error
                console.error('Failed to send message:', data.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    };

    if (!chat) {
        return <div className="w-full md:w-2/3 flex items-center justify-center text-gray-400">Select a chat to start messaging</div>;
    }

    return (
        <div className="flex flex-col bg-gray-800 h-full">
            <div className="flex items-center p-3 border-b border-gray-700">
                <button className="md:hidden mr-4 text-white" onClick={onBack}>
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <h3 className="text-white font-bold">{chat.username}</h3>
            </div>
            <div ref={chatContainerRef} className="flex-1 p-4 overflow-y-auto" onScroll={handleScroll}>
                {messagesLoading && <div className="text-white">Loading messages...</div>}
                {messagesError && <div className="text-red-500">{messagesError}</div>}
                {messages && messages.map(message => (
                    <div key={message.id} className={`flex ${message.sender !== chat['user-id'] ? 'justify-end' : 'justify-start'} mb-4`}>
                        <div className={`p-3 rounded-lg max-w-xs ${message.sender !== chat['user-id'] ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'}`}>
                            {message.message}
                        </div>
                    </div>
                ))}
            </div>
            <div className="p-4 bg-gray-800 border-t border-gray-700">
                <div className="relative">
                    <input 
                        type="text" 
                        placeholder="Type a message..." 
                        className="w-full bg-gray-700 text-white rounded-full py-3 px-4 focus:outline-none focus:ring-1 focus:ring-blue-500" 
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                    />
                    <button 
                        className="absolute right-0 top-0 h-full px-4 text-blue-500 hover:text-blue-400"
                        onClick={handleSendMessage}
                    >
                        Send
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ChatComponent;
