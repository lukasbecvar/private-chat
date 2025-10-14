import { useState, useEffect, useRef, useCallback, useLayoutEffect, forwardRef, useImperativeHandle } from 'react'
import { useConfig } from '../../hooks/useConfig'

// import loading component
import BouncingLoader from '../../sub-components/BouncingLoader'

// import app utils
import { TimestampFormatUtil } from '../../utils/TimestampFormatUtil'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faExclamationTriangle } from '@fortawesome/free-solid-svg-icons'

// import error screen component
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent'

// import default profile image asset
import defaultProfileImage from '../../assets/images/default-profile-image.png'

const MessagesListComponent = forwardRef(({ chat, onChatLoaded, messages, setMessages }, ref) => {
    // get config
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')
    const CHAT_LIMIT = getConfigValue('CHAT_LIMIT')
    const CHAT_POLLING_TIME_MS = getConfigValue('CHAT_POLLING_TIME_MS')

    // states
    const [error, setError] = useState(null)
    const [messagesError, setMessagesError] = useState(null)
    const [hasMoreMessages, setHasMoreMessages] = useState(true)
    const [messagesLoading, setMessagesLoading] = useState(false)
    const [isFetchingMoreMessages, setIsFetchingMoreMessages] = useState(false)
    const [currentOldestMessageOffset, setCurrentOldestMessageOffset] = useState(0)

    const isPollingRef = useRef(false)
    const messagesRef = useRef(messages)
    const chatContainerRef = useRef(null)
    messagesRef.current = messages
    const userScrolledUp = useRef(false)
    const scrollHeightBeforePrepend = useRef(null)

    // fetch initial chat history
    useEffect(() => {
        const fetchInitialChatHistory = async () => {
            if (!chat) {
                setMessages([])
                return
            }

            // enable loading indicator
            setMessagesError(null)
            setMessagesLoading(true)
            setHasMoreMessages(true)

            try {
                const countRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=1&offset=0`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get messages count
                const countData = await countRes.json()

                // check maintenance mode status
                if (countRes.status === 503) {
                    if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${countData.message}`)
                    setMessagesError(countData.message || 'Failed to fetch chat history')
                    return
                }

                // check if count data response is successful
                if (!countRes.ok || countData.status !== 'success') {
                    if (countRes.status === 404) {
                        setMessages([])
                        setMessagesError("This chat is empty \n You can start messaging by sending a message!")
                    } else {
                        if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${countData.message}`)
                        setMessagesError('Error fetching chat history: ' + countData.message)
                    }
                    return
                }

                // calculate initial limit and offset
                const initialLimit = CHAT_LIMIT
                const totalCount = countData['messages-count']
                const initialOffset = Math.max(0, totalCount - initialLimit)

                // get messages
                const messagesRes = await fetch(`${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=${initialLimit}&offset=${initialOffset}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get messages data from response
                const messagesData = await messagesRes.json()

                // check maintenance mode status
                if (messagesRes.status === 503) {
                    if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${messagesData.message}`)
                    setMessagesError(messagesData.message || 'Failed to fetch chat history.')
                    return
                }

                // check if messages data response is successful
                if (messagesRes.ok && messagesData.status === 'success') {
                    const messagesToDisplay = messagesData.data?.sort((a, b) => a.id - b.id) || []
                    setMessages(messagesToDisplay)
                    setCurrentOldestMessageOffset(initialOffset)
                    if (initialOffset === 0) {
                        setHasMoreMessages(false)
                    }
                    if (DEV_MODE) console.log(`[ChatComponent]: Initial messages displayed: ${messagesToDisplay.length}. Total count: ${totalCount}`)
                } else {
                    if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${messagesData.message}`)
                    setMessagesError('Error fetching chat history: ' + messagesData.message)
                }
            } catch (err) {
                if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${err.message}`)
                setMessagesError('Error fetching chat history: ' + err.message)
            } finally {
                setMessagesLoading(false)
                if (onChatLoaded) {
                    onChatLoaded()
                }
            }
        }

        fetchInitialChatHistory()
    }, [chat, DEV_MODE, API_TOKEN, API_URL, CHAT_LIMIT, onChatLoaded, setMessages])

    // fetch chat history (scroll to top for load old messages)
    const fetchChatHistory = async (offset, limit = CHAT_LIMIT, prepend = false, showLoadingIndicator = true) => {
        if (!chat || isFetchingMoreMessages) return

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

        try {
            // log info to console
            if (DEV_MODE) console.log(`[ChatComponent]: Fetching chat history for chat_id: ${chat['chat-id']}, limit: ${limit}, offset: ${offset}`)

            // fetch chat history
            let url = `${API_URL}/api/chat/history?chat_id=${chat['chat-id']}&limit=${limit}&offset=${offset}`
            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                }
            })

            // get data from response
            const data = await res.json()

            // check if response is successful
            if (res.ok && data.status === 'success') {
                if (DEV_MODE) {
                    console.log(`[ChatComponent]: Received ${data.data.length} messages. IDs: ${data.data.length > 0 ? `${data.data[0].id} - ${data.data[data.data.length - 1].id}` : 'N/A'}`)
                }
                if (data.data.length < limit || offset === 0) {
                    setHasMoreMessages(false)
                }
                if (prepend) {
                    setMessages(prevMessages => {
                        const existingMessageIds = new Set(prevMessages.map(msg => msg.id))
                        const newUniqueMessages = data.data.filter(msg => !existingMessageIds.has(msg.id))
                        const newMessages = [...newUniqueMessages, ...prevMessages]
                        if (DEV_MODE) console.log(`[ChatComponent]: Total messages on page: ${newMessages.length}`)
                        return newMessages
                    })
                } else {
                    const newMessages = data.data.reverse()
                    setMessages(newMessages)
                }

                setCurrentOldestMessageOffset(offset)
            } else {
                if (res.status === 404) {
                    setMessages([])
                    setMessagesError("This chat is empty \n You can start messaging by sending a message!")
                } else {
                    setMessagesError(data.message || 'Failed to fetch chat history')
                    if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${data.message}`)
                }
            }
        } catch (err) {
            setMessagesError('Error fetching chat history.')
            if (DEV_MODE) console.error(`[ChatComponent]: Error fetching chat history: ${err.message}`)
        } finally {
            if (!prepend && showLoadingIndicator) {
                setMessagesLoading(false)
            } else if (prepend) {
                setIsFetchingMoreMessages(false)
            }
        }
    }

    // poll new messages
    const pollNewMessages = useCallback(async () => {
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

            // get data from response
            const data = await res.json()

            // check maintenance mode status
            if (res.status === 503) {
                if (DEV_MODE) console.error(`[ChatComponent]: Error polling for new messages: ${data.message}`)
                setError(data.message || 'Failed to fetch chat history')
                return
            }

            // check if response is successful
            if (res.ok && data.status === 'success' && data.data.length > 0) {
                if (DEV_MODE) console.log('[ChatComponent]: Polling for new messages...')

                // check if user has scrolled up
                if (chatContainerRef.current) {
                    const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current
                    const isAtBottom = scrollHeight - scrollTop - clientHeight < 1
                    if (isAtBottom) {
                        userScrolledUp.current = false
                    }
                }

                // add new messages to the end of the list
                const newMessages = data.data.reverse()
                setMessages(prevMessages => {
                    const existingMessageIds = new Set(prevMessages.map(msg => msg.id))
                    const uniqueNewMessages = newMessages.filter(msg => !existingMessageIds.has(msg.id))
                    if (uniqueNewMessages.length > 0) {
                        const updatedMessages = [...prevMessages, ...uniqueNewMessages]
                        if (DEV_MODE) {
                            console.log(`[ChatComponent]: Polled ${uniqueNewMessages.length} new messages.`)
                            console.log(`[ChatComponent]: Total messages on page: ${updatedMessages.length}`)
                        }
                        return updatedMessages
                    }
                    return prevMessages
                })
            }
        } catch (err) {
            if (DEV_MODE) console.error(`[ChatComponent]: Error polling for new messages: ${err.message}`)
            setError('Polling for new messages failed: ' + err.message)
        } finally {
            isPollingRef.current = false
        }
    }, [chat, API_URL, API_TOKEN, DEV_MODE, setMessages])

    // poll new messages loop
    useEffect(() => {
        if (!chat) {
            return
        }

        const intervalId = setInterval(pollNewMessages, CHAT_POLLING_TIME_MS)
        return () => clearInterval(intervalId)
    }, [chat, pollNewMessages, CHAT_POLLING_TIME_MS])

    // handle scroll event
    const handleScroll = () => {
        if (chatContainerRef.current) {
            const { scrollTop, scrollHeight, clientHeight } = chatContainerRef.current

            if (scrollTop < 2 && hasMoreMessages && !isFetchingMoreMessages) {
                const newOffset = Math.max(0, currentOldestMessageOffset - CHAT_LIMIT)
                fetchChatHistory(newOffset, CHAT_LIMIT, true)
            }

            const isAtBottom = scrollHeight - scrollTop - clientHeight < 1
            userScrolledUp.current = !isAtBottom
        }
    }

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

    useEffect(() => {
        if (chatContainerRef.current && chat) {
            userScrolledUp.current = false
            chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight
        }
    }, [chat])

    useImperativeHandle(ref, () => ({
        pollNewMessages,
        resetUserScrolledUp: () => {
            userScrolledUp.current = false
        },
    }))

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error} noNavbarOffset={true}/>

    // render messages list
    return (
        <div ref={chatContainerRef} className="flex-1 p-3 overflow-y-auto" onScroll={handleScroll}>
            {isFetchingMoreMessages && <BouncingLoader/>}
            {messagesLoading && <BouncingLoader/>}

            {/* render error message */}
            {messagesError && (
                <div className="text-red-500 text-center whitespace-pre-line mt-10">
                    {messagesError}
                </div>
            )}

            {/* render messages */}
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
                            <div className="text-center text-gray-500 text-xs my-4">
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
                                <div className="inline-block max-w-xs md:max-w-2xl">
                                <div
                                    className={`p-3 break-words whitespace-pre-wrap 
                                        ${isCurrentUser 
                                            ? (message.error ? 'bg-red-800/80' : 'bg-blue-600') 
                                            : (message.error ? 'bg-red-800/80' : 'bg-gray-700/60')
                                        } 
                                        ${isCurrentUser ? 'text-white' : 'text-gray-200'}
                                        ${message.error ? 'rounded-t-lg' : 'rounded-2xl'}
                                    `}
                                    >
                                    {message.message}
                                </div>
                                    {message.error && (
                                        <div className={`p-2 break-words whitespace-pre-wrap bg-red-800/50 text-red-200 rounded-b-lg text-xs flex items-center`}>
                                            <FontAwesomeIcon icon={faExclamationTriangle} className="mr-2"/>
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
    )
})

export default MessagesListComponent
