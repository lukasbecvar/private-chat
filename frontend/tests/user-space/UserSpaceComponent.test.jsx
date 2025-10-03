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
    const MockChatListComponent = ({ onChatListLoad, onSelectChat, onChatListLoaded }) => {
        const chats = [{ id: 1, username: 'Chat 1' }, { id: 2, username: 'Chat 2' }];
        React.useEffect(() => {
            const chats = [{ id: 1, username: 'Chat 1' }, { id: 2, username: 'Chat 2' }];
            onChatListLoad(chats);
            if (onChatListLoaded) {
                onChatListLoaded();
            }
        }, [onChatListLoad, onChatListLoaded]);

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
            ChatComponent: {chat?.username}
            <button onClick={onBack}>Back</button>
        </div>
    )
}))

globalThis.fetch = vi.fn()

describe('UserSpaceComponent', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    // setup fetch mock
    beforeEach(() => {
        vi.resetAllMocks()
        global.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
        })
    })

    // test render loading component
    it('shows loading initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        
        // render user space component
        render(<UserSpaceComponent/>)
        
        // expect loading
        expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
    })

    // test render error screen component if fetch returns error
    it('renders ErrorScreenComponent if fetch returns error', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ message: 'Unauthorized' })
        })

        // render user space component
        render(<UserSpaceComponent/>)

        // expect error screen
        expect(await screen.findByText(/Error: Unauthorized/i)).toBeInTheDocument()
    })

    // test render error screen component if fetch throws
    it('renders generic error if fetch throws', async () => {
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
        fetch.mockRejectedValue(new Error('Network fail'))

        // render user space component
        render(<UserSpaceComponent/>)

        // expect error screen
        expect(await screen.findByText(/Error: Error fetching user info/i)).toBeInTheDocument()
        expect(consoleSpy).toHaveBeenCalledWith(expect.stringContaining('Error fetching user info'))
        consoleSpy.mockRestore()
    })

    // test select first chat automatically when chat list loads
    it('selects first chat automatically when chat list loads', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
        })

        // render user space component
        render(<UserSpaceComponent/>)

        // expect Chat 1 to be selected
        expect(await screen.findByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
    })

    // test switch to another chat on selection
    it('switches to another chat on selection', async () => {
        render(<UserSpaceComponent/>)

        // wait for Chat 1 to appear
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
        })

        // click to select Chat 2
        fireEvent.click(screen.getByText('Select Chat 2'))

        // wait for Chat 2
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 2/i)).toBeInTheDocument()
        })
    })

    // test show chat list and hide chat component on back button click (mobile view)
    it('shows chat list and hides chat component on back button click (mobile view)', async () => {
        const { container } = render(<UserSpaceComponent/>)

        // wait until chat is rendered
        await waitFor(() => {
            expect(screen.getByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()
        })

        const chatListComponentContainer = container.querySelector('[data-testid="chat-list"]')
        const chatComponentContainer = container.querySelector('[data-testid="chat-component"]')

        // check initial state: list hidden, chat visible
        expect(chatListComponentContainer).toHaveClass('hidden')
        expect(chatComponentContainer).not.toHaveClass('hidden')

        // click back
        fireEvent.click(await screen.findByText('Back'))

        // after back: list visible, chat hidden
        await waitFor(() => {
            expect(chatListComponentContainer).not.toHaveClass('hidden')
            expect(chatComponentContainer).toHaveClass('hidden')
        })
    })
})
