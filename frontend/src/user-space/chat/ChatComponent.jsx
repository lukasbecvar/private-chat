import React, { useState, useEffect, useRef } from 'react';

const ChatComponent = ({ chat, messages, messagesLoading, messagesError, onBack, onSendMessage, onLoadMoreMessages, hasMoreMessages, isFetchingMoreMessages, currentUser, API_URL, API_TOKEN }) => {
    const [newMessage, setNewMessage] = useState('');
    const [isSending, setIsSending] = useState(false);
    const chatContainerRef = useRef(null);
    const inputRef = useRef(null);
    const [shouldScrollToBottom, setShouldScrollToBottom] = useState(true);
    const prevScrollHeight = useRef(0);

    const handleScroll = () => {
        if (chatContainerRef.current) {
            const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current;

            if (scrollTop === 0 && hasMoreMessages && !isFetchingMoreMessages) {
                onLoadMoreMessages();
            }

            if (scrollHeight - scrollTop <= clientHeight + 100) {
                setShouldScrollToBottom(true);
            } else {
                setShouldScrollToBottom(false);
            }
        }
    };

    useEffect(() => {
        if (chatContainerRef.current) {
            if (shouldScrollToBottom) {
                chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
            } else if (isFetchingMoreMessages) {
                chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight - prevScrollHeight.current;
            }
            prevScrollHeight.current = chatContainerRef.current.scrollHeight;
        }
    }, [messages, shouldScrollToBottom, isFetchingMoreMessages]);

    useEffect(() => {
        if (chatContainerRef.current && chat) {
            chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
            setShouldScrollToBottom(true);
        }
    }, [chat]);

    const handleSendMessage = async () => {
        const trimmedMessage = newMessage.trim();
        if (trimmedMessage === '' || isSending) return;

        setIsSending(true);
        const success = await onSendMessage(trimmedMessage);
        setIsSending(false);

        if (success) {
            setNewMessage('');
            setTimeout(() => {
                if (inputRef.current) {
                    inputRef.current.focus();
                }
            }, 0);
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
                {isFetchingMoreMessages && <div className="text-white text-center">Loading more messages...</div>}
                {messagesLoading && <div className="text-white text-center">Loading messages...</div>}
                {messagesError && <div className="text-red-500 text-center">{messagesError}</div>}
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
                        ref={inputRef}
                        type="text" 
                        placeholder="Type a message..." 
                        className="w-full bg-gray-700 text-white rounded-full py-3 px-4 focus:outline-none focus:ring-1 focus:ring-blue-500" 
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                        disabled={isSending}
                    />
                    <button 
                        className="absolute right-0 top-0 h-full px-4 text-blue-500 hover:text-blue-400 disabled:opacity-50"
                        onClick={handleSendMessage}
                        disabled={isSending}
                    >
                        {isSending ? 'Sending...' : 'Send'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ChatComponent;
