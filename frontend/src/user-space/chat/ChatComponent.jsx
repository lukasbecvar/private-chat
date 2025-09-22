import React, { useState, useEffect, useRef, useCallback } from 'react';
import defaultProfileImage from '../../assets/images/default-profile-image.png';
import { useConfig } from '../../hooks/useConfig';
import { formatTimestamp } from '../../utils/formatTimestamp';
import BouncingLoader from '../../sub-components/BouncingLoader';
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent';

const ChatComponent = ({ chat, onBack, currentUser }) => {
    const { getConfigValue } = useConfig();
    const API_URL = getConfigValue('API_URL');
    const DEV_MODE = getConfigValue('DEV_MODE');
    const API_TOKEN = getConfigValue('API_TOKEN');
    const CHAT_LIMIT = getConfigValue('CHAT_LIMIT');
    const CHAT_POLLING_TIME = getConfigValue('CHAT_POLLING_TIME');

    const [messages, setMessages] = useState([]);
    const [messagesLoading, setMessagesLoading] = useState(false);
    const [messagesError, setMessagesError] = useState(null);
    const [hasMoreMessages, setHasMoreMessages] = useState(true);
    const [isFetchingMoreMessages, setIsFetchingMoreMessages] = useState(false);
    const [currentOldestMessageOffset, setCurrentOldestMessageOffset] = useState(0);
    const [totalMessagesCount, setTotalMessagesCount] = useState(0);
    const isPollingRef = useRef(false);

    const [error, setError] = useState(null);

    const [newMessage, setNewMessage] = useState('');
    const [isSending, setIsSending] = useState(false);
    const chatContainerRef = useRef(null);
    const inputRef = useRef(null);
    const [shouldScrollToBottom, setShouldScrollToBottom] = useState(true);
    const prevScrollHeight = useRef(0);

    const fetchChatHistory = async (offset, limit = CHAT_LIMIT, prepend = false, showLoadingIndicator = true) => {
        if (!chat || isFetchingMoreMessages) return;

        if (!prepend && showLoadingIndicator) {
            setMessagesLoading(true);
            setMessagesError(null);
            setHasMoreMessages(true);
            setMessages([]);
            setIsFetchingMoreMessages(false);
        } else if (prepend) {
            setIsFetchingMoreMessages(true);
        }

        try {
            let url = `${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=${limit}&offset=${offset}`;
            if (DEV_MODE) console.log(`Fetching chat history for chat_id: ${chat['chat-id']}, limit: ${limit}, offset: ${offset}`);

            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                }
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                if (DEV_MODE) {
                    console.log(`Received ${data.data.length} messages. IDs: ${data.data.length > 0 ? `${data.data[0].id} - ${data.data[data.data.length - 1].id}` : 'N/A'}`);
                }
                if (data.data.length < limit || offset === 0) {
                    setHasMoreMessages(false);
                }
                if (prepend) {
                    setMessages(prevMessages => {
                        const existingMessageIds = new Set(prevMessages.map(msg => msg.id));
                        const newUniqueMessages = data.data.filter(msg => !existingMessageIds.has(msg.id));
                        const newMessages = [...newUniqueMessages, ...prevMessages];
                        if (DEV_MODE) console.log(`[ChatComponent] Total messages on page: ${newMessages.length}`);
                        return newMessages;
                    });
                } else {
                    const newMessages = data.data.reverse();
                    setMessages(newMessages);
                }

                setCurrentOldestMessageOffset(offset);
            } else {
                if (res.status === 404) {
                    setMessages([]);
                    setMessagesError("This chat is empty \n You can start messaging by sending a message!");
                } else {
                    setError(data.message || 'Failed to fetch chat history');
                }
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error fetching chat history: ' + err.message);
            setMessagesError('Error fetching chat history.');
        } finally {
            if (!prepend && showLoadingIndicator) {
                setMessagesLoading(false);
            } else if (prepend) {
                setIsFetchingMoreMessages(false);
            }
        }
    };

    useEffect(() => {
        const fetchInitialChatHistory = async () => {
            if (!chat) {
                setMessages([]);
                return;
            }

            setMessagesLoading(true);
            setMessagesError(null);
            setHasMoreMessages(true);
            setTotalMessagesCount(0);

            const allMessagesRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=1000000&offset=0`, {
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
                
                const messagesToDisplay = allMessages.slice(initialOffset).sort((a, b) => a.id - b.id);
                setMessages(messagesToDisplay);
                setCurrentOldestMessageOffset(initialOffset);
                setMessagesLoading(false);
                if (DEV_MODE) console.log(`[ChatComponent] Initial messages displayed: ${messagesToDisplay.length}. Total count: ${count}`);
                if (initialOffset === 0) {
                    setHasMoreMessages(false);
                }

            } else {
                console.error('Failed to fetch all messages for count', allMessagesData.message);
                
                if (allMessagesRes.status === 404) {
                    setMessages([]);
                    setMessagesError("This chat is empty \n You can start messaging by sending a message!");
                } else {
                    setError(allMessagesData.message);
                }
                setMessagesLoading(false);
            }
        }

        fetchInitialChatHistory();
    }, [chat, DEV_MODE, API_TOKEN, API_URL, CHAT_LIMIT]);

    const handleSendMessage = async (message) => {
        const trimmedMessage = message.trim();
        if (trimmedMessage === '' || isSending) return;

        setIsSending(true);
        try {
            const res = await fetch(`${API_URL}/api/chat/send`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                },
                body: JSON.stringify({
                    message: trimmedMessage,
                    receiver_id: chat['user-id'],
                }),
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                await pollNewMessages();
                if (messagesError !== null) {
                    setMessagesError(null);
                }
                setNewMessage('');
                setShouldScrollToBottom(true);
                setTimeout(() => {
                    if (inputRef.current) {
                        inputRef.current.focus();
                    }
                }, 0);
            } else {
                setError(data.message);
                console.error('Failed to send message:', data.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            setIsSending(false);
        }
    };

    const pollNewMessages = useCallback(async () => {
        if (!chat || isPollingRef.current) {
            return;
        }
        isPollingRef.current = true;

        const lastPermanentMessage = [...messages].reverse().find(msg => msg.id);
        const lastMessageId = lastPermanentMessage ? lastPermanentMessage.id : 0;

        try {
            const res = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&after_id=${lastMessageId}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN,
                }
            });

            const data = await res.json();

            if (res.ok && data.status === 'success' && data.data.length > 0) {
                if (DEV_MODE) console.log('[ChatComponent] Polling for new messages...');
                const newMessages = data.data.reverse();
                setMessages(prevMessages => {
                    const existingMessageIds = new Set(prevMessages.map(msg => msg.id));
                    const uniqueNewMessages = newMessages.filter(msg => !existingMessageIds.has(msg.id));
                    if (uniqueNewMessages.length > 0) {
                        const updatedMessages = [...prevMessages, ...uniqueNewMessages];
                        if (DEV_MODE) {
                            console.log(`[ChatComponent] Polled ${uniqueNewMessages.length} new messages.`);
                            console.log(`[ChatComponent] Total messages on page: ${updatedMessages.length}`);
                        }
                        return updatedMessages;
                    }
                    return prevMessages;
                });
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error polling for new messages: ' + err.message);
            setError('Polling for new messages failed: ' + err.message);
        } finally {
            isPollingRef.current = false;
        }
    }, [chat, messages, API_URL, API_TOKEN, DEV_MODE]);

    useEffect(() => {
        if (!chat) {
            return;
        }

        const intervalId = setInterval(pollNewMessages, CHAT_POLLING_TIME);

        return () => clearInterval(intervalId);
    }, [chat, pollNewMessages, CHAT_POLLING_TIME]);

    const handleScroll = () => {
        if (chatContainerRef.current) {
            const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current;

            if (scrollTop === 0 && hasMoreMessages && !isFetchingMoreMessages) {
                const newOffset = Math.max(0, currentOldestMessageOffset - CHAT_LIMIT);
                fetchChatHistory(newOffset, CHAT_LIMIT, true);
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

    if (error) return <ErrorScreenComponent errorMessage={error}/>;

    if (!chat) {
        return <div className="w-full md:w-2/2 mt-10 flex items-center justify-center text-2xl text-gray-400">Select a chat to start messaging</div>;
    }

    return (
        <div className="flex flex-col bg-gray-800 h-full">
            <div className="flex items-center p-3 border-b border-gray-700">
                <button className="md:hidden mr-4 text-white" onClick={onBack}>
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <img 
                    src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']} 
                    alt={chat.username} 
                    className="w-8 h-8 rounded-full mr-3 border border-gray-400/50"
                />
                <h3 className="text-white font-bold">{chat.username}</h3>
            </div>
            <div ref={chatContainerRef} className="flex-1 p-4 overflow-y-auto" onScroll={handleScroll}>
                {isFetchingMoreMessages && <BouncingLoader />}
                {messagesLoading && <BouncingLoader />}
                {messagesError && (
                    <div className="text-red-500 text-center whitespace-pre-line mt-10">
                        {messagesError}
                    </div>
                )}
                {messages && messages.map((message, index) => {
                    const nextMessage = messages[index + 1];
                    const showImage = !nextMessage || nextMessage.sender !== message.sender;

                    const isCurrentUser = message.sender !== chat['user-id'];
                    const profilePicture = chat['profile-picture'];

                    const previousMessage = messages[index - 1];
                    const showTimestamp = (!previousMessage || (new Date(message.timestamp) - new Date(previousMessage.timestamp)) > 3600 * 1000) || index === messages.length - 1;

                    return (
                        <div key={message.id}>
                            {showTimestamp && (
                                <div className="text-center text-gray-500 text-xs my-2">
                                    {formatTimestamp(message.timestamp)}
                                </div>
                            )}
                            <div className={`flex items-end ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-2`}>
                                {!isCurrentUser && showImage && (
                                    <img 
                                        src={profilePicture === 'nonset-default' ? defaultProfileImage : profilePicture} 
                                        alt="Sender" 
                                        className="w-6 h-6 rounded-full mr-2"
                                    />
                                )}
                                <div className={`p-3 rounded-lg max-w-xs md:max-w-xl break-words whitespace-pre-wrap ${isCurrentUser ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'} ${!isCurrentUser && !showImage ? 'ml-8' : ''}`}>
                                    {message.message}
                                </div>
                            </div>
                        </div>
                    )
                })}            </div>
            <div className="p-2 bg-gray-800 border-t border-gray-700">
                <div className="relative">
                    <input
                        ref={inputRef}
                        type="text" 
                        placeholder="Type a message..." 
                        className="w-full bg-gray-700 text-white rounded-lg py-2 px-2 pr-16 focus:outline-none focus:ring-1 focus:ring-blue-500" 
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSendMessage(newMessage)}
                        disabled={isSending}
                    />
                    <button 
                        className="absolute right-0 top-0 h-full px-4 text-blue-500 hover:text-blue-400 disabled:opacity-50"
                        onClick={() => handleSendMessage(newMessage)}
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
