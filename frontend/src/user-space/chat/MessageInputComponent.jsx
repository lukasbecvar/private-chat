import { useState, useRef } from 'react'
import { useConfig } from '../../hooks/useConfig'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faPaperPlane } from '@fortawesome/free-solid-svg-icons'

export default function MessageInputComponent({ chat, onMessageSent, setMessages }) {
    // get config
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const inputRef = useRef(null)
    const [newMessage, setNewMessage] = useState('')
    const [isSending, setIsSending] = useState(false)

    // handle message sending
    const handleSendMessage = async (message) => {
        const trimmedMessage = message.trim()
        if (trimmedMessage === '' || isSending) return

        // set sending state
        setIsSending(true)

        try {
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

            // check if message was sent successfully
            if (res.ok && data.status === 'success') {
                onMessageSent()
                setNewMessage('')
                setTimeout(() => {
                    if (inputRef.current) {
                        inputRef.current.focus()
                    }
                }, 100)
            } else {
                if (DEV_MODE) console.error(`Error sending message: ${data.message}`)
                const temporaryId = Date.now()
                const failedMessage = {
                    sender: null,
                    id: temporaryId,
                    message: trimmedMessage,
                    timestamp: new Date().toISOString(),
                    error: data.message || 'Failed to send message.'
                }
                setMessages(prevMessages => [...prevMessages, failedMessage])
                setNewMessage('')
            }
        } catch (error) {
            if (DEV_MODE) console.error(`Error sending message: ${error.message}`)
            const temporaryId = Date.now()
            const failedMessage = {
                sender: null,
                id: temporaryId,
                message: trimmedMessage,
                timestamp: new Date().toISOString(),
                error: error.message || 'Failed to send message.'
            }
            setMessages(prevMessages => [...prevMessages, failedMessage])
            setNewMessage('')
        } finally {
            setIsSending(false)
        }
    }

    // return message input box
    return (
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
    )
}
