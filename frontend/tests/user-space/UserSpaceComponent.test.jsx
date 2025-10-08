import React from 'react'
import { describe, it, expect, vi, afterEach, beforeEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'

// import tested component
import UserSpaceComponent from '../../src/user-space/UserSpaceComponent'

// mock config hook
vi.mock('../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                DEV_MODE: false,
                API_TOKEN: 'test-token'
            }
            return config[key]
        }
    })
}))

// mock sub-components
vi.mock('../../src/sub-components/LoadingComponent', () => ({
    default: () => <div data-testid="loading">Loading...</div>
}))
vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div data-testid="error-screen">{errorMessage}</div>
}))
vi.mock('../../src/user-space/elements/NavbarElement', () => ({
    default: ({ userData }) => <div data-testid="navbar">{userData?.username}</div>
}))
vi.mock('../../src/user-space/chat/ChatListComponent', () => ({
    default: function ChatListComponent({ onSelectChat, onChatListLoad, onChatListLoaded }) {
        React.useEffect(() => {
            const chats = [{ 'chat-id': 1, username: 'User1' }]
            onChatListLoad(chats)
            onChatListLoaded()
        }, [onChatListLoad, onChatListLoaded])
        return (
            <div data-testid="chat-list-component">
                <button onClick={() => onSelectChat({ 'chat-id': 1, username: 'User1' })}>Select Chat</button>
            </div>
        )
    }
}))
vi.mock('../../src/user-space/chat/ChatComponent', () => ({
    default: function ChatComponent({ chat, onBack, onChatLoaded }) {
        React.useEffect(() => {
            onChatLoaded()
        }, [onChatLoaded])
        if (!chat) return <div data-testid="no-chat-selected">No chat selected</div>
        return (
            <div data-testid="chat-component">
                <span>{chat.username}</span>
                <button onClick={onBack}>Back</button>
            </div>
        )
    }
}))

global.fetch = vi.fn()

describe('UserSpaceComponent', () => {
    beforeEach(() => {
        global.innerWidth = 1024 // desktop resolution
    })

    afterEach(() => {
        vi.clearAllMocks()
    })

    // test render loading component
    it('renders Loading component initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        render(<UserSpaceComponent/>)

        // expect loading component to be rendered
        expect(screen.getByTestId('loading')).toBeInTheDocument()
    })

    // test render error component (user data fetching failed)
    it('renders ErrorScreenComponent if fetching user data fails', async () => {
        fetch.mockRejectedValue(new Error('Fetch failed'))
        render(<UserSpaceComponent/>)

        // expect error screen to be rendered
        expect(await screen.findByTestId('error-screen')).toBeInTheDocument()
        expect(screen.getByText('Error fetching user info.')).toBeInTheDocument()
    })

    // test render layout correctly after fetching user data
    it('renders layout correctly after fetching user data', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { id: 1, username: 'testuser' } })
        })
        render(<UserSpaceComponent/>)

        // expect layout to be rendered
        expect(await screen.findByTestId('navbar')).toBeInTheDocument()
        expect(screen.getByTestId('chat-list-component')).toBeInTheDocument()
        expect(screen.getByText('testuser')).toBeInTheDocument()
    })

    // test selects the first chat automatically on desktop
    it('selects the first chat automatically on desktop', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { id: 1, username: 'testuser' } })
        })

        render(<UserSpaceComponent/>)

        // wait for initial layout
        await waitFor(() => {
            expect(screen.getByTestId('chat-component-wrapper')).toBeInTheDocument()
            expect(screen.getByText('User1')).toBeInTheDocument()
        })
    })

    // test shows chat component when a chat is selected and hides it on back
    it('shows chat component when a chat is selected and hides it on back', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { id: 1, username: 'testuser' } })
        })

        render(<UserSpaceComponent/>)

        // wait for initial layout
        const selectChatButton = await screen.findByRole('button', { name: /Select Chat/i })

        // select a chat
        fireEvent.click(selectChatButton)

        // chat component should be visible
        const chatComponent = await screen.findByTestId('chat-component')
        expect(chatComponent).toBeInTheDocument()
        expect(screen.getByText('User1')).toBeInTheDocument()

        // go back
        const backButton = screen.getByRole('button', { name: /Back/i })
        fireEvent.click(backButton)

        // chat component should be hidden
        await waitFor(() => {
            expect(screen.getByTestId('no-chat-selected')).toBeInTheDocument()
        })
    })

    // test responsive behavior on mobile
    it('hides chat list and shows chat component on mobile when a chat is selected', async () => {
        global.innerWidth = 500 // mobile resolution

        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { id: 1, username: 'testuser' } })
        })

        render(<UserSpaceComponent/>)

        // wait for the initial loading to complete
        await waitFor(() => {
            expect(screen.getByTestId('no-chat-selected')).toBeInTheDocument()
        })

        const chatList = screen.getByTestId('chat-list')
        const chatComponentWrapper = screen.getByTestId('chat-component-wrapper')

        // after load on mobile list is visible and chat component is hidden
        expect(chatList).not.toHaveClass('hidden')
        expect(chatComponentWrapper).toHaveClass('hidden')

        // select a chat
        const selectChatButton = screen.getByRole('button', { name: /Select Chat/i })
        fireEvent.click(selectChatButton)

        // after selection chat list is hidden and chat component is visible
        await waitFor(() => {
            expect(chatList).toHaveClass('hidden')
            expect(chatComponentWrapper).not.toHaveClass('hidden')
            expect(screen.getByTestId('chat-component')).toBeInTheDocument()
        })
    })
})
