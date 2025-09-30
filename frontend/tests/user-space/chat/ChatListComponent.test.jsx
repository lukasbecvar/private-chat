
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import ChatListComponent from '../../../src/user-space/chat/ChatListComponent'

// mock config
vi.mock('../../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: false
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

global.fetch = vi.fn()

// mock chat list
const mockChats = [
    {
        'chat-id': 'chat-1',
        username: 'Alice',
        roles: ['ROLE_USER'],
        'profile-picture': 'nonset-default',
        'last-message': {
            'sender-name': 'Alice',
            message: 'Hi there!'
        }
    },
    {
        'chat-id': 'chat-2',
        username: 'Bob',
        roles: ['ROLE_USER', 'ROLE_ADMIN'],
        'profile-picture': 'nonset-default',
        'last-message': {
            'sender-name': 'CurrentUser',
            message: 'See you soon'
        }
    }
]

// mock current user
const mockCurrentUser = {
    username: 'CurrentUser'
}

describe('ChatListComponent', () => {
    beforeEach(() => {
        fetch.mockClear()
    })

    it('shows loading indicator initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        render(<ChatListComponent />)

        expect(screen.getByTestId('bouncing-loader')).toBeInTheDocument()
    })

    it('renders chat list on successful fetch', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent />)

        await waitFor(() => {
            expect(screen.getByText('Alice')).toBeInTheDocument()
            expect(screen.getByText('Bob')).toBeInTheDocument()
        })
    })

    it('displays an error message if fetch fails', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ status: 'error', message: 'Failed to fetch' })
        })

        render(<ChatListComponent />)

        await waitFor(() => {
            expect(screen.getByText('Error: Failed to fetch')).toBeInTheDocument()
        })
    })

    it('calls onSelectChat when a chat is clicked', async () => {
        const onSelectChat = vi.fn()
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent onSelectChat={onSelectChat} />)

        await waitFor(() => {
            fireEvent.click(screen.getByText('Alice'))
        })

        expect(onSelectChat).toHaveBeenCalledWith(expect.objectContaining({ username: 'Alice' }))
    })

    it('calls onChatListLoad when chat list is loaded', async () => {
        const onChatListLoad = vi.fn()
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent onChatListLoad={onChatListLoad} />)

        await waitFor(() => {
            expect(onChatListLoad).toHaveBeenCalledWith(
                expect.arrayContaining([
                    expect.objectContaining({ username: 'Alice' })
                ])
            )
        })
    })

    it('filters the chat list based on the search term', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent/>)

        await waitFor(() => {
            expect(screen.getByText('Alice')).toBeInTheDocument()
            expect(screen.getByText('Bob')).toBeInTheDocument()
        })

        const searchInput = screen.getByPlaceholderText('Search username...')
        fireEvent.change(searchInput, { target: { value: 'Alice' } })

        expect(screen.getByText('Alice')).toBeInTheDocument()
        expect(screen.queryByText('Bob')).not.toBeInTheDocument()
    })

    it('displays "You" for last message sent by current user', async () => {
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent currentUser={mockCurrentUser} />)

        await waitFor(() => {
            const bobChat = screen.getByText('Bob').closest('.flex.items-center')
            expect(bobChat).toHaveTextContent('You: See you soon')
        })
    })

    it('displays sender name for last message not sent by current user', async () => {
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent currentUser={mockCurrentUser} />)

        await waitFor(() => {
            const aliceChat = screen.getAllByText('Alice')[0].closest('.flex.items-center')
            expect(aliceChat).toHaveTextContent('Alice: Hi there!')
        })
    })

    it('highlights the selected chat', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        const selectedChat = mockChats[0]

        render(<ChatListComponent selectedChat={selectedChat} />)

        await waitFor(() => {
            const bobChat = screen.getByText('Bob').closest('.flex.items-center')
            const aliceChat = screen.getByText('Alice').closest('.flex.items-center')

            expect(aliceChat).toHaveClass('bg-gray-700')
            expect(bobChat).not.toHaveClass('bg-gray-700')
        })
    })

    it('renders admin username with red color', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        render(<ChatListComponent />)

        await waitFor(() => {
            const aliceUsername = screen.getByText('Alice')
            const bobUsername = screen.getByText('Bob')

            expect(aliceUsername).not.toHaveClass('text-red-400')
            expect(bobUsername).toHaveClass('text-red-400')
        })
    })
})
