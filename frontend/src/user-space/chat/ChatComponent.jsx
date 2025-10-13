import { useState, useRef } from 'react'

// import app components
import MessagesListComponent from './MessagesListComponent'
import MessageInputComponent from './MessageInputComponent'
import ErrorScreenComponent from '../../sub-components/ErrorScreenComponent'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faChevronLeft } from '@fortawesome/free-solid-svg-icons'

// import default profile image asset
import defaultProfileImage from '../../assets/images/default-profile-image.png'

export default function ChatComponent({ chat, onBack, onChatLoaded }) {
    // states
    const messageListRef = useRef(null)
    const [error, setError] = useState(null)
    const [messages, setMessages] = useState([])

    // poll new messages on message sent
    const handleMessageSent = () => {
        if (messageListRef.current) {
            messageListRef.current.resetUserScrolledUp()
            messageListRef.current.pollNewMessages()
        }
    }

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    // show info when chat is not selected
    if (!chat) return <div className="w-full md:w-2/2 mt-10 flex items-center justify-center text-2xl text-gray-400">Select a chat to start messaging</div>

    return (
        <div className="flex flex-col bg-gray-800/70 h-full">
            <div className="flex items-center p-1.5 border-b border-gray-700/50 shadow-sm">
                <button className="md:hidden mr-1 text-white" onClick={onBack} aria-label="Back">
                    <FontAwesomeIcon icon={faChevronLeft} className="h-6 w-6 mt-1"/>
                </button>
                <img
                    alt={chat.username}
                    className="w-8 h-8 rounded-full mr-2 border-2 border-gray-600"
                    src={chat['profile-picture'] === 'nonset-default' ? defaultProfileImage : chat['profile-picture']}
                />
                <h3 className="text-white font-semibold text-lg">{chat.username}</h3>
            </div>

            {/* chat messages list */}
            <MessagesListComponent
                chat={chat}
                messages={messages}
                ref={messageListRef}
                setError={setError}
                setMessages={setMessages}
                onChatLoaded={onChatLoaded}
            />

            {/* new message input */}
            <MessageInputComponent
                chat={chat}
                setMessages={setMessages}
                onMessageSent={handleMessageSent}
            />
        </div>
    )
}
