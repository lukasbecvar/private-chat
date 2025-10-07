import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'

// import chat component
import ChatComponent from '../../../src/user-space/chat/ChatComponent'

vi.mock('../../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: () => {}
    })
}))

vi.mock('../../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

vi.mock('../../../src/user-space/chat/MessagesListComponent', () => ({
    default: () => <div data-testid="message-list"></div>
}))

vi.mock('../../../src/user-space/chat/MessageInputComponent', () => ({
    default: () => <div data-testid="message-input"></div>
}))

const mockChat = {
    'chat-id': 'chat-123',
    'user-id': 'user-456',
    username: 'JohnDoe',
    'profile-picture': 'nonset-default'
}

describe('ChatComponent', () => {
    // test rendering info message when no chat is provided
    it('renders "Select a chat" message when no chat is provided', () => {
        render(<ChatComponent chat={null} onBack={() => {}}/>)
        expect(screen.getByText('Select a chat to start messaging')).toBeInTheDocument()
    })

    // test handling back button click
    it('handles back button click', () => {
        const onBack = vi.fn()
        render(<ChatComponent chat={mockChat} onBack={onBack}/>)
        const backButton = screen.getByRole('button', { name: /back/i })
        fireEvent.click(backButton)
        expect(onBack).toHaveBeenCalledTimes(1)
    })

    // test rendering the chat header correctly
    it('renders the chat header correctly', () => {
        render(<ChatComponent chat={mockChat} onBack={() => {}}/>)
        expect(screen.getByText('JohnDoe')).toBeInTheDocument()
        expect(screen.getByAltText('JohnDoe')).toBeInTheDocument()
    })
})
