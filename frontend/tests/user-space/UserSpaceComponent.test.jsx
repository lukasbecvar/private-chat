import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, afterEach, beforeEach } from 'vitest'
import UserSpaceComponent from '../../src/user-space/UserSpaceComponent'

// mock config
vi.mock('../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: true
            }
            return config[key]
        }
    })
}))

// mock sub-components
vi.mock('../../src/user-space/elements/NavbarElement', () => ({
    default: ({ userData }) => <div>Navbar: {userData?.username}</div>
}))
vi.mock('../../src/sub-components/LoadingComponent', () => ({
    default: () => <div>Loading...</div>
}))
vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock chat list component (bez setTimeout!)
vi.mock('../../src/user-space/chat/ChatListComponent', () => {
    const React = require('react');
    const MockChatListComponent = ({ onChatListLoad, onSelectChat }) => {
        const chats = [{ id: 1, name: 'Chat 1' }, { id: 2, name: 'Chat 2' }];
        React.useEffect(() => {
            const chats = [{ id: 1, name: 'Chat 1' }, { id: 2, name: 'Chat 2' }];
            onChatListLoad(chats);
        }, [onChatListLoad]);

        return (
            <div>
                <div>ChatListComponent</div>
                <button onClick={() => onSelectChat(chats[1])}>Select Chat 2</button>
            </div>
        );
    };
    return { default: MockChatListComponent };
})

// mock chat component
vi.mock('../../src/user-space/chat/ChatComponent', () => ({
    default: ({ chat, onBack }) => (
        <div>
            ChatComponent: {chat?.name}
            <button onClick={onBack}>Back</button>
        </div>
    )
}))

globalThis.fetch = vi.fn()

describe('UserSpaceComponent', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    beforeEach(() => {
        vi.resetAllMocks()
        global.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
        })
    })

    it('shows loading initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        render(<UserSpaceComponent />)
        expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
    })

    it('renders ErrorScreenComponent if fetch returns error', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ message: 'Unauthorized' })
        })

        render(<UserSpaceComponent />)
        expect(await screen.findByText(/Error: Unauthorized/i)).toBeInTheDocument()
    })

    it('renders generic error if fetch throws', async () => {
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
        fetch.mockRejectedValue(new Error('Network fail'))

        render(<UserSpaceComponent />)

        expect(await screen.findByText(/Error: Error fetching user info/i)).toBeInTheDocument()
        expect(consoleSpy).toHaveBeenCalledWith(expect.stringContaining('Error fetching user info'))
        consoleSpy.mockRestore()
    })

    it('selects first chat automatically when chat list loads', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
        })

        render(<UserSpaceComponent />)
        expect(await screen.findByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
    })

    it('switches to another chat on selection', async () => {
        render(<UserSpaceComponent />)

        // wait for Chat 1 to appear
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
        })

        // Click to select Chat 2
        fireEvent.click(screen.getByText('Select Chat 2'))

        // wait for Chat 2
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 2/i)).toBeInTheDocument()
        })
    })

    it('shows chat list and hides chat component on back button click (mobile view)', async () => {
        const { container } = render(<UserSpaceComponent />)

        // wait until chat is rendered
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
        })

        const chatListComponentContainer = container.querySelector('[data-testid="chat-list"]')
        const chatComponentContainer = container.querySelector('[data-testid="chat-component"]')

        // Check initial state: list hidden, chat visible
        expect(chatListComponentContainer).toHaveClass('hidden')
        expect(chatComponentContainer).not.toHaveClass('hidden')

        // Click back
        fireEvent.click(await screen.findByText('Back'))

        // After back: list visible, chat hidden
        await waitFor(() => {
            expect(chatListComponentContainer).not.toHaveClass('hidden')
            expect(chatComponentContainer).toHaveClass('hidden')
        })
    })
})
