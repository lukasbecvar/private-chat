
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'

// import tested component
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
    // reset fetch mock before test
    beforeEach(() => {
        fetch.mockClear()
    })

    // test render loading component
    it('shows loading indicator initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        render(<ChatListComponent onChatListLoaded={() => {}}/>)

        // expect loading component
        expect(screen.getByTestId('bouncing-loader')).toBeInTheDocument()
    })

    // test render chat list on successful fetch
    it('renders chat list on successful fetch', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent/>)

        // wait for chat list to be displayed
        await waitFor(() => {
            expect(screen.getByText('Alice')).toBeInTheDocument()
            expect(screen.getByText('Bob')).toBeInTheDocument()
        })
    })

    // test render error message if fetch fails
    it('displays an error message if fetch fails', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ status: 'error', message: 'Failed to fetch' })
        })

        // render chat list component
        render(<ChatListComponent/>)

        // wait for error message to be displayed
        await waitFor(() => {
            expect(screen.getByText('Error: Failed to fetch')).toBeInTheDocument()
        })
    })

    // test select chat when clicking on
    it('calls onSelectChat when a chat is clicked', async () => {
        const onSelectChat = vi.fn()
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent onSelectChat={onSelectChat}/>)

        // wait for chat to be selected
        await waitFor(() => {
            fireEvent.click(screen.getByText('Alice'))
        })

        // expect onSelectChat to be called
        expect(onSelectChat).toHaveBeenCalledWith(expect.objectContaining({ username: 'Alice' }))
    })

    // test call onChatListLoad when chat list is loaded
    it('calls onChatListLoad when chat list is loaded', async () => {
        const onChatListLoad = vi.fn()
        
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent onChatListLoad={onChatListLoad}/>)

        // wait for onChatListLoad to be called
        await waitFor(() => {
            expect(onChatListLoad).toHaveBeenCalledWith(
                expect.arrayContaining([
                    expect.objectContaining({ username: 'Alice' })
                ])
            )
        })
    })

    // test search filter the chat list
    it('filters the chat list based on the search term', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent/>)

        // wait for chat list to be displayed
        await waitFor(() => {
            expect(screen.getByText('Alice')).toBeInTheDocument()
            expect(screen.getByText('Bob')).toBeInTheDocument()
        })

        // search Alice in chat list
        const searchInput = screen.getByPlaceholderText('Search username...')
        fireEvent.change(searchInput, { target: { value: 'Alice' } })

        // expect Alice to be displayed
        expect(screen.getByText('Alice')).toBeInTheDocument()
        
        // expect Bob to be hidden
        expect(screen.queryByText('Bob')).not.toBeInTheDocument()
    })

    // test last message for current user
    it('displays "You" for last message sent by current user', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent currentUser={mockCurrentUser}/>)

        // wait for last message to be displayed
        await waitFor(() => {
            const bobChat = screen.getByText('Bob').closest('.flex.items-center')
            expect(bobChat).toHaveTextContent('You: See you soon')
        })
    })

    // test last message prefix for last message not sent by current user
    it('displays sender name for last message not sent by current user', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent currentUser={mockCurrentUser}/>)

        // wait for last message to be displayed
        await waitFor(() => {
            const aliceChat = screen.getAllByText('Alice')[0].closest('.flex.items-center')
            expect(aliceChat).toHaveTextContent('Alice: Hi there!')
        })
    })

    // test highlight selected chat
    it('highlights the selected chat', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        const selectedChat = mockChats[0]

        // render chat list component
        render(<ChatListComponent selectedChat={selectedChat}/>)

        // wait for chat to be highlighted
        await waitFor(() => {
            const bobChat = screen.getByText('Bob').closest('.flex.items-center')
            const aliceChat = screen.getByText('Alice').closest('.flex.items-center')

            expect(aliceChat).toHaveClass('bg-gray-700')
            expect(bobChat).not.toHaveClass('bg-gray-700')
        })
    })

    // test admin username with red color
    it('renders admin username with red color', async () => {
        // mock fetch response
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: mockChats })
        })

        // render chat list component
        render(<ChatListComponent/>)

        // wait for admin username to be displayed
        await waitFor(() => {
            const aliceUsername = screen.getByText('Alice')
            const bobUsername = screen.getByText('Bob')

            expect(aliceUsername).not.toHaveClass('text-red-400')
            expect(bobUsername).toHaveClass('text-red-400')
        })
    })
})
