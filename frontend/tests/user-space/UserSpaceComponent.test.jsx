import { render, screen } from '@testing-library/react'
import { describe, it, expect, vi, afterEach } from 'vitest'
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

// mock chat list component
vi.mock('../../src/user-space/chat/ChatListComponent', () => ({
    default: ({ onChatListLoad, onSelectChat }) => {
        const chats = [{ id: 1, name: 'Chat 1' }, { id: 2, name: 'Chat 2' }]
        setTimeout(() => {
            onChatListLoad(chats)
        }, 0)

        return (
            <div>
                <div>ChatListComponent</div>
                <button onClick={() => onSelectChat(chats[1])}>Select Chat 2</button>
            </div>
        )
    }
}))

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

    // it('switches to another chat on selection', async () => {
    //     fetch.mockResolvedValue({
    //         ok: true,
    //         json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
    //     })

    //     render(<UserSpaceComponent />)

    //     // Initially, Chat 1 is selected
    //     expect(await screen.findByText(/ChatComponent: Chat 1/i)).toBeInTheDocument()

    //     // Click to select Chat 2
    //     const selectButton = screen.getByText('Select Chat 2')
    //     fireEvent.click(selectButton)

    //     // Now, Chat 2 should be displayed
    //     expect(await screen.findByText(/ChatComponent: Chat 2/i)).toBeInTheDocument()
    // })

    // it('shows chat list and hides chat component on back button click (mobile view)', async () => {
    //     fetch.mockResolvedValue({
    //         ok: true,
    //         json: async () => ({ status: 'success', data: { username: 'JohnDoe' } })
    //     })

    //     const { container } = render(<UserSpaceComponent />)

    //     // Initially, Chat 1 is selected and visible
    //     await screen.findByText(/ChatComponent: Chat 1/i)

    //     const chatListComponentContainer = container.querySelector('.md\\:w-1\\/4')
    //     const chatComponentContainer = container.querySelector('.md\\:w-2\\/2')

    //     // In the initial state (desktop-first), both are flex
    //     expect(chatListComponentContainer).toHaveClass('md:flex')
    //     expect(chatComponentContainer).toHaveClass('md:flex')

    //     // After a chat is selected, the list is hidden on mobile
    //     expect(chatListComponentContainer).toHaveClass('hidden')

    //     // Click the back button inside ChatComponent
    //     const backButton = screen.getByText('Back')
    //     fireEvent.click(backButton)

    //     // Now the chat list should be visible, and the chat component hidden
    //     await waitFor(() => {
    //         expect(chatListComponentContainer).not.toHaveClass('hidden')
    //         expect(chatComponentContainer).toHaveClass('hidden')
    //     })
    // })



})
