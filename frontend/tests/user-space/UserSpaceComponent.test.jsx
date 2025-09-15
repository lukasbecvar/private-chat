import { render, screen } from '@testing-library/react'
import { describe, it, expect, vi, afterEach } from 'vitest'
import UserSpaceComponent from '../../src/user-space/UserSpaceComponent'

// mock app config
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

// mock app components
vi.mock('../../src/user-space/elements/NavbarElement', () => ({
    default: ({ userData }) => <div>Navbar: {userData?.username}</div>
}))
vi.mock('../../src/sub-components/LoadingComponent', () => ({
    default: () => <div>Loading...</div>
}))
vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock fetch
globalThis.fetch = vi.fn()

describe('UserSpaceComponent', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    it('shows loading initially', () => {
        fetch.mockImplementation(() => new Promise(() => { }))
        render(<UserSpaceComponent/>)
        
        // expect loading
        expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
    })

    it('renders Navbar and content if fetch is successful', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success', data: { username: 'JohnDoe', status: 'online' } })
        })

        render(<UserSpaceComponent/>)

        // expect navbar
        expect(await screen.findByText(/Navbar: JohnDoe/i)).toBeInTheDocument()
        
        // expect content (only testing lorem ipsum text)
        expect(screen.getByText(/Lorem ipsum dolor sit amet/i)).toBeInTheDocument()
    })

    it('renders ErrorScreenComponent if fetch returns error', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ message: 'Unauthorized' })
        })

        render(<UserSpaceComponent/>)

        // expect error screen
        expect(await screen.findByText(/Error: Unauthorized/i)).toBeInTheDocument()
    })

    it('renders generic error if fetch throws', async () => {
        fetch.mockRejectedValue(new Error('Network fail'))

        render(<UserSpaceComponent/>)

        // expect error screen
        expect(await screen.findByText(/Error: Error fetching user info/i)).toBeInTheDocument()
    })
})
