import { describe, it, expect, vi, afterEach } from 'vitest'
import { render, screen, fireEvent } from '@testing-library/react'
import TokenCheckContainer from '../../src/auth/TokenCheckContainer'

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
vi.mock('../../src/auth/LoginComponent', () => ({
    default: function LoginComponent({ onLoginSuccess }) {
        return (
            <div data-testid="login-component">
                <button onClick={onLoginSuccess}>Login</button>
            </div>
        )
    }
}))
vi.mock('../../src/sub-components/LoadingComponent', () => ({
    default: () => <div data-testid="loading">Loading...</div>
}))
vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div data-testid="error-screen">{errorMessage}</div>
}))

global.fetch = vi.fn()

describe('TokenCheckContainer', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    // test render loading component
    it('renders Loading component initially', () => {
        fetch.mockImplementation(() => new Promise(() => {}))
        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)
        
        // expect loading component to be rendered
        expect(screen.getByTestId('loading')).toBeInTheDocument()
    })

    // test render error component (aunthenticated)
    it('renders LoginComponent when user is unauthenticated (401)', async () => {
        fetch.mockResolvedValue({
            ok: false,
            status: 401,
            json: async () => ({ message: 'Unauthorized' })
        })
        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)
        
        // expect login component to be rendered
        expect(await screen.findByTestId('login-component')).toBeInTheDocument()
    })

    // test render children (authenticated)
    it('renders children when user is authenticated', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ username: 'testuser' })
        })
        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)
        
        // expect children to be rendered
        expect(await screen.findByText('Children')).toBeInTheDocument()
    })

    // test render error component (API error)
    it('renders ErrorScreenComponent on API error (non-401)', async () => {
        fetch.mockResolvedValue({
            ok: false,
            status: 500,
            json: async () => ({ message: 'Server Error' })
        })
        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)
        
        // expect error screen to be rendered
        expect(await screen.findByTestId('error-screen')).toBeInTheDocument()
        expect(screen.getByText('Server Error')).toBeInTheDocument()
    })

    // test render error component (network error)
    it('renders ErrorScreenComponent on network error', async () => {
        fetch.mockRejectedValue(new Error('Network Error'))
        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)
        
        // expect error screen to be rendered
        expect(await screen.findByTestId('error-screen')).toBeInTheDocument()
        expect(screen.getByText('Network Error')).toBeInTheDocument()
    })

    // test transition from unauthenticated to authenticated view on successful login
    it('transitions from login to authenticated view on successful login', async () => {
        // initial state: unauthenticated
        fetch.mockResolvedValueOnce({
            ok: false,
            status: 401,
            json: async () => ({ message: 'Unauthorized' })
        })

        render(<TokenCheckContainer><div>Children</div></TokenCheckContainer>)

        // wait for login component to appear
        const loginButton = await screen.findByRole('button', { name: /Login/i })
        expect(screen.getByTestId('login-component')).toBeInTheDocument()

        // simulate successful login
        fireEvent.click(loginButton)

        // now the component should show children
        expect(await screen.findByText('Children')).toBeInTheDocument()
    })
})
