
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'

// import messages list component
import MessagesListComponent from '../../../src/user-space/chat/MessagesListComponent'

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

vi.mock('../../../src/sub-components/BouncingLoader', () => ({
    default: () => <div data-testid="bouncing-loader">Loading...</div>
}))

vi.mock('../../../src/utils/TimestampFormatUtil', () => ({
    TimestampFormatUtil: (ts) => new Date(ts).toLocaleString()
}))

global.fetch = vi.fn()

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

describe('MessagesListComponent', () => {
    beforeEach(() => {
        fetch.mockClear()
    })

    // test shows loading indicator initially and then displays messages
    it('shows loading indicator initially and then displays messages', async () => {
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', 'messages-count': mockMessages.length })
        }).mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success', data: mockMessages })
        })

        const setMessages = vi.fn()
        render(<MessagesListComponent chat={mockChat} messages={[]} setMessages={setMessages}/>)

        expect(screen.getByTestId('bouncing-loader')).toBeInTheDocument()

        // wait for messages to be displayed
        await waitFor(() => {
            expect(setMessages).toHaveBeenCalledWith(mockMessages.sort((a, b) => a.id - b.id))
        })
    })

    // test displays an error message if fetching history fails
    it('displays an error message if fetching history fails', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ status: 'error', message: 'Failed to fetch' })
        })

        const setMessages = vi.fn()

        render(<MessagesListComponent chat={mockChat} messages={[]} setMessages={setMessages}/>)

        // expect error message
        await waitFor(() => {
            expect(screen.getByText('Error fetching chat history: Failed to fetch')).toBeInTheDocument()
        })
    })
})
