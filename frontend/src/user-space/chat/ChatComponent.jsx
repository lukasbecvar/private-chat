import { useState, useEffect, useRef, useCallback, useLayoutEffect } from 'react'
import { useConfig } from '../../hooks/useConfig'
import { TimestampFormatUtil } from '../../utils/TimestampFormatUtil'

// import app components
import BouncingLoader from '../../sub-components/BouncingLoader'
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent'

// import default profile image asset
import defaultProfileImage from '../../assets/images/default-profile-image.png'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faPaperPlane, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons'

export default function ChatComponent({ chat, onBack, onChatLoaded }) {
    // get config
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')
    const CHAT_LIMIT = getConfigValue('CHAT_LIMIT')
    const CHAT_POLLING_TIME = getConfigValue('CHAT_POLLING_TIME')

    // fatal error state
    const [error, setError] = useState(null)

    // history polling states
    const isPollingRef = useRef(false)
    const [messages, setMessages] = useState([])
    const [messagesError, setMessagesError] = useState(null)
    const [hasMoreMessages, setHasMoreMessages] = useState(true)
    const [messagesLoading, setMessagesLoading] = useState(false)
    const [isFetchingMoreMessages, setIsFetchingMoreMessages] = useState(false)
    const [currentOldestMessageOffset, setCurrentOldestMessageOffset] = useState(0)

    // message sending states
    const inputRef = useRef(null)
    const messagesRef = useRef(messages)
    const chatContainerRef = useRef(null)
    messagesRef.current = messages // keep ref updated with the latest messages

    const scrollHeightBeforePrepend = useRef(null)
    const userScrolledUp = useRef(false)
    const [newMessage, setNewMessage] = useState('')
    const [isSending, setIsSending] = useState(false)

    // fetch chat history
    const fetchChatHistory = async (offset, limit = CHAT_LIMIT, prepend = false, showLoadingIndicator = true) => {
        if (!chat || isFetchingMoreMessages) return

        // manage loading indicators for initial load vs fetching older messages
        if (!prepend && showLoadingIndicator) {
            setMessagesLoading(true)
            setMessagesError(null)
            setHasMoreMessages(true)
            setMessages([])
            setIsFetchingMoreMessages(false)
        } else if (prepend) {
            if (chatContainerRef.current) {
                scrollHeightBeforePrepend.current = chatContainerRef.current.scrollHeight
            }
            setIsFetchingMoreMessages(true)
        }

        // fetch chat history for infinite scroll
        try {
            // log fetching chat history
            if (DEV_MODE) console.log(`Fetching chat history for chat_id: ${chat['chat-id']}, limit: ${limit}, offset: ${offset}`)

            // fetch chat history
            let url = `${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=${limit}&offset=${offset}`
            const res = await fetch(url, {
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
                if (DEV_MODE) {
                    console.log(`Received ${data.data.length} messages. IDs: ${data.data.length > 0 ? `${data.data[0].id} - ${data.data[data.data.length - 1].id}` : 'N/A'}`)
                }
                if (data.data.length < limit || offset === 0) {
                    setHasMoreMessages(false)
                }
                // add messages to history
                if (prepend) {
                    setMessages(prevMessages => {
                        const existingMessageIds = new Set(prevMessages.map(msg => msg.id))
                        const newUniqueMessages = data.data.filter(msg => !existingMessageIds.has(msg.id))
                        const newMessages = [...newUniqueMessages, ...prevMessages]
                        if (DEV_MODE) console.log(`[ChatComponent] Total messages on page: ${newMessages.length}`)
                        return newMessages
                    })
                } else {
                    const newMessages = data.data.reverse()
                    setMessages(newMessages)
                }

                // set current oldest message offset
                setCurrentOldestMessageOffset(offset)
            } else {
                // chat not found status
                if (res.status === 404) {
                    setMessages([])
                    setMessagesError("This chat is empty \n You can start messaging by sending a message!")
                } else {
                    setError(data.message || 'Failed to fetch chat history')
                    if (DEV_MODE) console.error('Error fetching chat history: ' + data.message)
                }
            }
        } catch (err) {
            setMessagesError('Error fetching chat history.')
            if (DEV_MODE) console.error('Error fetching chat history: ' + err.message)
        } finally {
            if (!prepend && showLoadingIndicator) {
                setMessagesLoading(false)
            } else if (prepend) {
                setIsFetchingMoreMessages(false)
            }
        }
    }

    // fetch initial chat history (chat open)
    useEffect(() => {
        const fetchInitialChatHistory = async () => {
            // clean chat history if chat is not set
            if (!chat) {
                setMessages([])
                return
            }

            // enable loading indicator
            setMessagesError(null)
            setMessagesLoading(true)
            setHasMoreMessages(true)

            try {
                // first fetch just to get total messages count
                const countRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=1&offset=0`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get response data
                const countData = await countRes.json()

                // handle maintenance mode
                if (countRes.status === 503) {
                    if (DEV_MODE) console.error('Error fetching chat history: ' + countData.message)
                    setError(countData.message || 'Failed to fetch chat history')
                    return
                }

                // check if response was successful
                if (!countRes.ok || countData.status !== 'success') {
                    if (countRes.status === 404) {
                        setMessages([])
                        setMessagesError("This chat is empty \n You can start messaging by sending a message!")
                    } else {
                        if (DEV_MODE) console.error('Error fetching chat history: ' + countData.message)
                        setError('Error fetching chat history: ' + countData.message)
                    }
                    return
                }

                const initialLimit = CHAT_LIMIT
                const totalCount = countData['messages-count']
                const initialOffset = Math.max(0, totalCount - initialLimit)

                // fetch initial chat history
                const messagesRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=${initialLimit}&offset=${initialOffset}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get response data
                const messagesData = await messagesRes.json()

                // handle maintenance mode
                if (messagesRes.status === 503) {
                    if (DEV_MODE) console.error('Error fetching chat history: ' + messagesData.message)
                    setError(messagesData.message || 'Failed to fetch chat history.')
                    return
                }

                // check if response was successful
                if (messagesRes.ok && messagesData.status === 'success') {
                    const messagesToDisplay = messagesData.data?.sort((a, b) => a.id - b.id) || []
                    setMessages(messagesToDisplay)
                    setCurrentOldestMessageOffset(initialOffset)
                    if (initialOffset === 0) {
                        setHasMoreMessages(false)
                    }
                    if (DEV_MODE) console.log(`[ChatComponent] Initial messages displayed: ${messagesToDisplay.length}. Total count: ${totalCount}`)
                } else {
                    if (DEV_MODE) console.error('Error fetching chat history: ' + messagesData.message)
                    setError('Error fetching chat history: ' + messagesData.message)
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error fetching chat history: ' + err.message)
                setError('Error fetching chat history: ' + err.message)
            } finally {
                setMessagesLoading(false)
                if (onChatLoaded) {
                    onChatLoaded()
                }
            }
        }

        fetchInitialChatHistory()
    }, [chat, DEV_MODE, API_TOKEN, API_URL, CHAT_LIMIT, onChatLoaded])

    // send message to chat
    const handleSendMessage = async (message) => {
        const trimmedMessage = message.trim()

        // check if message is empty or sending
        if (trimmedMessage === '' || isSending) return

        // set sending state
        setIsSending(true)
        userScrolledUp.current = false // force scroll to bottom for new message

        try {
            // send message to API
            const res = await fetch(`${API_URL}/api/chat/send`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                },
                body: JSON.stringify({
                    message: trimmedMessage,
                    receiver_id: chat['user-id']
                })
            })

            // get response data
            const data = await res.json()

            // check if response was successful
            if (res.ok && data.status === 'success') {
                await pollNewMessages()
                if (messagesError !== null) {
                    setMessagesError(null)
                }
                setNewMessage('')
                setTimeout(() => {
                    if (inputRef.current) {
                        inputRef.current.focus()
                    }
                }, 0)
            } else {
                if (DEV_MODE) console.error('Error sending message: ' + data.message)
                
                // add failed message to chat history
                const temporaryId = Date.now()
                const failedMessage = {
                    id: temporaryId,
                    message: trimmedMessage,
                    sender: null,
                    timestamp: new Date().toISOString(),
                    error: data.message || 'Failed to send message.'
                }
                setMessages(prevMessages => [...prevMessages, failedMessage])
            }
        } catch (error) {
            if (DEV_MODE) console.error('Error sending message: ' + error.message)
            
            // add failed message to chat history
            const temporaryId = Date.now()
            const failedMessage = {
                id: temporaryId,
                message: trimmedMessage,
                sender: null,
                timestamp: new Date().toISOString(),
                error: error.message || 'Failed to send message.'
            }
            setMessages(prevMessages => [...prevMessages, failedMessage])
        } finally {
            setIsSending(false)
        }
    }

    // new messages polling mechanism
    const pollNewMessages = useCallback(async () => {
        // check if chat is not set or polling is already in progress
        if (!chat || isPollingRef.current) {
            return
        }
        isPollingRef.current = true

        const lastPermanentMessage = [...messagesRef.current].reverse().find(msg => msg.id && !msg.error)
        const lastMessageId = lastPermanentMessage ? lastPermanentMessage.id : 0

        try {
            // fetch new messages
            const res = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&after_id=${lastMessageId}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                }
            })

            // get response data
            const data = await res.json()

            // handle maintenance mode
            if (res.status === 503) {
                if (DEV_MODE) console.error('Error polling for new messages: ' + data.message)
                setError(data.message || 'Failed to fetch chat history')
                return
            }

            // check if response was successful
            if (res.ok && data.status === 'success' && data.data.length > 0) {
                if (DEV_MODE) console.log('[ChatComponent] Polling for new messages...')

                // check if user is scrolled to the bottom before adding new messages
                if (chatContainerRef.current) {
                    const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current
                    const isAtBottom = scrollHeight - scrollTop - clientHeight < 1
                    if (isAtBottom) {
                        userScrolledUp.current = false
                    }
                }

                const newMessages = data.data.reverse()
                setMessages(prevMessages => {
                    const existingMessageIds = new Set(prevMessages.map(msg => msg.id))
                    const uniqueNewMessages = newMessages.filter(msg => !existingMessageIds.has(msg.id))
                    if (uniqueNewMessages.length > 0) {
                        const updatedMessages = [...prevMessages, ...uniqueNewMessages]
                        if (DEV_MODE) {
                            console.log(`[ChatComponent] Polled ${uniqueNewMessages.length} new messages.`)
                            console.log(`[ChatComponent] Total messages on page: ${updatedMessages.length}`)
                        }
                        return updatedMessages
                    }
                    return prevMessages
                })
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error polling for new messages: ' + err.message)
            setError('Polling for new messages failed: ' + err.message)
        } finally {
            isPollingRef.current = false
        }
    }, [chat, API_URL, API_TOKEN, DEV_MODE])

    // start new messages polling mechanism
    useEffect(() => {
        // check if chat is not set
        if (!chat) {
            return
        }

        // start messages polling
        const intervalId = setInterval(pollNewMessages, CHAT_POLLING_TIME)
        return () => clearInterval(intervalId)
    }, [chat, pollNewMessages, CHAT_POLLING_TIME])

    // handle chat scroll
    const handleScroll = () => {
        if (chatContainerRef.current) {
            const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current

            // if user scrolled to very top -> try to load older messages (pagination upwards)
            if (scrollTop < 2 && hasMoreMessages && !isFetchingMoreMessages) {
                const newOffset = Math.max(0, currentOldestMessageOffset - CHAT_LIMIT)
                fetchChatHistory(newOffset, CHAT_LIMIT, true)
            }

            // check if user has scrolled up from the bottom
            const isAtBottom = scrollHeight - scrollTop - clientHeight < 1
            userScrolledUp.current = !isAtBottom
        }
    }

    // adjusts scroll position after messages update or when fetching more
    useLayoutEffect(() => {
        if (chatContainerRef.current) {
            if (scrollHeightBeforePrepend.current !== null) {
                const newScrollHeight = chatContainerRef.current.scrollHeight
                chatContainerRef.current.scrollTop = newScrollHeight - scrollHeightBeforePrepend.current
                scrollHeightBeforePrepend.current = null
            } else if (!userScrolledUp.current) {
                chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight
            }
        }
    }, [messages])

    // when switching to another chat -> always jump to the latest message at bottom
    useEffect(() => {
        if (chatContainerRef.current && chat) {
            userScrolledUp.current = false // reset scroll lock
            chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight
        }
    }, [chat])

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    // show info when chat is not selected
    if (!chat) {
        return <div className="w-full md:w-2/2 mt-10 flex items-center justify-center text-2xl text-gray-400">Select a chat to start messaging</div>
    }

    // render chat component
    return (
        <div className="flex flex-col bg-gray-800 h-full">

            {/* chat title */}
            <div className="flex items-center p-1.5 border-b border-gray-700">
                <button className="md:hidden mr-4 text-white" onClick={onBack} aria-label="Back">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
                <img
                    src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                    alt={chat.username}
                    className="w-8 h-8 rounded-full mr-3 border border-gray-400/50"
                />
                <h3 className="text-white font-bold">{chat.username}</h3>
            </div>

            <div ref={chatContainerRef} className="flex-1 p-3 overflow-y-auto" onScroll={handleScroll}>
                {/* loading elements */}
                {isFetchingMoreMessages && <BouncingLoader/>}
                {messagesLoading && <BouncingLoader/>}

                {/* error message */}
                {messagesError && (
                    <div className="text-red-500 text-center whitespace-pre-line mt-10">
                        {messagesError}
                    </div>
                )}

                {/* chat messages */}
                {messages && messages.map((message, index) => {
                    const nextMessage = messages[index + 1]
                    const showImage = !nextMessage || nextMessage.sender !== message.sender

                    const isCurrentUser = message.sender === null || message.sender !== chat['user-id']
                    const profilePicture = chat['profile-picture']

                    const previousMessage = messages[index - 1]
                    const showTimestamp = !previousMessage || (new Date(message.timestamp) - new Date(previousMessage.timestamp)) > 3600 * 1000

                    return (
                        <div key={message.id}>
                            {showTimestamp && (
                                <div className="text-center text-gray-500 text-xs my-2">
                                    {TimestampFormatUtil(message.timestamp)}
                                </div>
                            )}
                            <div className={`flex items-end ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-2`}>
                                {!isCurrentUser && showImage && (
                                    <img
                                        alt="Sender"
                                        className="w-6 h-6 rounded-full mr-2"
                                        src={profilePicture === 'nonset-default' ? defaultProfileImage : profilePicture}
                                    />
                                )}
                                <div className={`flex flex-col ${isCurrentUser ? 'items-end' : 'items-start'} ${!isCurrentUser && !showImage ? 'ml-8' : ''}`}>
                                    <div className="inline-block max-w-xs md:max-w-xl">
                                        <div className={`p-3 break-words whitespace-pre-wrap ${isCurrentUser ? (message.error ? 'bg-blue-800' : 'bg-blue-700') : (message.error ? 'bg-gray-800' : 'bg-gray-700')} ${isCurrentUser ? 'text-white' : 'text-gray-300'} ${message.error ? 'rounded-t-lg' : 'rounded-lg'}`}>
                                            {message.message}
                                        </div>
                                        {message.error && (
                                            <div className={`p-3 break-words whitespace-pre-wrap bg-red-800 text-red-200 rounded-b-lg text-xs flex items-center`}>
                                                <FontAwesomeIcon icon={faExclamationTriangle} className="mr-2" />
                                                {message.error}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )
                })}
            </div>

            {/* new message input */}
            <div className="p-0.5 bg-gray-800 border-t border-gray-700">
                <div className="relative">
                    <input
                        type="text"
                        ref={inputRef}
                        value={newMessage}
                        disabled={isSending}
                        placeholder="Type a message..."
                        onChange={(e) => setNewMessage(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSendMessage(newMessage)}
                        className="w-full bg-gray-700 text-white py-2 px-2 pr-10 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <button
                        disabled={isSending}
                        data-testid="send-button"
                        onClick={() => handleSendMessage(newMessage)}
                        className="absolute right-0 top-0 h-full px-3 text-blue-500 hover:text-blue-400 disabled:opacity-50"
                    >
                        {isSending ? '...' : <FontAwesomeIcon icon={faPaperPlane}/>}
                    </button>
                </div>
            </div>
        </div>
    )
}
