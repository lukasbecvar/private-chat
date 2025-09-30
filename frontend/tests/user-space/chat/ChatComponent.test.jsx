import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import ChatComponent from '../../../src/user-space/chat/ChatComponent'

// mock config
vi.mock('../../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: false,
                CHAT_LIMIT: 20,
                CHAT_POLLING_TIME: 5000
            }
            return config[key]
        }
    })
}))

// mock sub-components
vi.mock('../../../src/sub-components/BouncingLoader', () => ({
    default: () => <div data-testid="bouncing-loader">Loading...</div>
}))
vi.mock('../../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock timestamp formatting
vi.mock('../../../src/utils/TimestampFormatUtil', () => ({
    TimestampFormatUtil: (ts) => new Date(ts).toLocaleString()
}))

global.fetch = vi.fn()

// mock chat data
const mockChat = {
    'chat-id': 'chat-123',
    'user-id': 'user-456',
    username: 'JohnDoe',
    'profile-picture': 'nonset-default'
}
const mockMessages = [
    { id: 1, sender: 'user-456', message: 'Hello', timestamp: '2025-09-24T10:00:00Z' },
    { id: 2, sender: 'user-789', message: 'Hi there!', timestamp: '2025-09-24T10:01:00Z' }
]

describe('ChatComponent', () => {
    beforeEach(() => {
        fetch.mockClear()
    })

    it('renders "Select a chat" message when no chat is provided', () => {
        render(<ChatComponent chat={null} onBack={() => {}} />)

        expect(screen.getByText('Select a chat to start messaging')).toBeInTheDocument()
    })

    it('shows loading indicator initially and then displays messages', async () => {
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', 'messages-count': mockMessages.length })
        }).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', data: mockMessages })
        })

        render(<ChatComponent chat={mockChat} onBack={() => {}} />)

        expect(screen.getByTestId('bouncing-loader')).toBeInTheDocument()

        await waitFor(() => {
            expect(screen.getByText('Hello')).toBeInTheDocument()
            expect(screen.getByText('Hi there!')).toBeInTheDocument()
        })
    })

    it('displays an error message if fetching history fails', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ status: 'error', message: 'Failed to fetch' })
        })

        render(<ChatComponent chat={mockChat} onBack={() => {}} />)

        await waitFor(() => {
            expect(screen.getByText('Error: Error fetching chat history: Failed to fetch')).toBeInTheDocument()
        })
    })

    it('sends a message when user types and clicks send', async () => {
        // mock initial history fetch
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', 'messages-count': 0 })
        }).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', data: [] })
        })

        // mock send message fetch
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success' })
        })

        // mock polling fetch after sending
        const sentMessage = { id: 3, sender: 'user-789', message: 'Test message', timestamp: '2025-09-24T10:05:00Z' }
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', data: [sentMessage] })
        })

        render(<ChatComponent chat={mockChat} onBack={() => {}} />)

        const input = screen.getByPlaceholderText('Type a message...')
        const sendButton = screen.getByTestId('send-button')

        fireEvent.change(input, { target: { value: 'Test message' } })
        fireEvent.click(sendButton)

        // expect send button to be disabled while sending
        expect(sendButton).toBeDisabled()

        await waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(
                'http://mock-api/api/chat/send',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify({
                        message: 'Test message',
                        receiver_id: 'user-456'
                    })
                })
            )
        })
        
        await waitFor(() => {
            expect(screen.getByText('Test message')).toBeInTheDocument()
        })

        expect(input.value).toBe('')
    })

    it('handles back button click', () => {
        const onBack = vi.fn()
        render(<ChatComponent chat={mockChat} onBack={onBack} />)
        
        const backButton = screen.getByRole('button', { name: /back/i })
        fireEvent.click(backButton)

        expect(onBack).toHaveBeenCalledTimes(1)
    })
})
