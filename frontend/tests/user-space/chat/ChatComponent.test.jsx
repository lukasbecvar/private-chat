import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'

// import tested component
import ChatComponent from '../../../src/user-space/chat/ChatComponent'

// mock config hook
vi.mock('../../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: () => {}
    })
}))

// mock error screen component
vi.mock('../../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock chat components
vi.mock('../../../src/user-space/chat/MessagesListComponent', () => ({
    default: () => <div data-testid="message-list"></div>
}))
vi.mock('../../../src/user-space/chat/MessageInputComponent', () => ({
    default: () => <div data-testid="message-input"></div>
}))

const mockChat = {
    username: 'JohnDoe',
    'chat-id': 'chat-123',
    'user-id': 'user-456',
    'profile-picture': 'nonset-default'
}

describe('ChatComponent', () => {
    // test rendering info message when chat is not selected
    it('renders "Select a chat" message when chat is not selected', () => {
        render(<ChatComponent chat={null} onBack={() => {}}/>)

        // expect info message
        expect(screen.getByText('Select a chat to start messaging')).toBeInTheDocument()
    })

    // test handling back button click
    it('handles back button click', () => {
        const onBack = vi.fn()
        render(<ChatComponent chat={mockChat} onBack={onBack}/>)
        const backButton = screen.getByRole('button', { name: /back/i })
        fireEvent.click(backButton)

        // expect onBack to be called
        expect(onBack).toHaveBeenCalledTimes(1)
    })

    // test rendering the chat header correctly
    it('renders the chat header correctly', () => {
        render(<ChatComponent chat={mockChat} onBack={() => {}}/>)

        // expect chat header to be rendered
        expect(screen.getByText('JohnDoe')).toBeInTheDocument()
        expect(screen.getByAltText('JohnDoe')).toBeInTheDocument()
    })
})
