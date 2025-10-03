import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import LoginComponent from '../../src/auth/LoginComponent.jsx'

// mock app config
vi.mock('../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const mockConfig = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: true,
            }
            return mockConfig[key]
        }
    })
}))

// mock loading component
vi.mock('../../src/sub-components/LoadingComponent', () => ({
    default: () => <div data-testid="loading">Mock Loading</div>
}))

describe('LoginComponent', () => {
    let onLoginSuccess

    // reset fetch mock before test
    beforeEach(() => {
        onLoginSuccess = vi.fn()
        globalThis.fetch = vi.fn()
    })

    // test render login component
    it('renders expected elements', () => {
        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)

        // expect elements
        expect(screen.getByPlaceholderText('Username')).toBeInTheDocument()
        expect(screen.getByPlaceholderText('Password')).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })

    // test error messages
    it('shows error if username is empty', async () => {
        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)
        fireEvent.click(screen.getByRole('button', { name: /sign in/i }))

        // expect error message
        expect(await screen.findByText('Username cannot be empty.')).toBeInTheDocument()
    })

    // test error messages
    it('shows error if password is empty', async () => {
        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)
        fireEvent.change(screen.getByPlaceholderText('Username'), { target: { value: 'user' } })
        fireEvent.click(screen.getByRole('button', { name: /sign in/i }))
        
        // expect error message
        expect(await screen.findByText('Password cannot be empty.')).toBeInTheDocument()
    })

    // test error messages
    it('shows error if login fails', async () => {
        // mock failed fetch response
        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({ message: 'Invalid credentials' })
        })

        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)
        fireEvent.change(screen.getByPlaceholderText('Username'), { target: { value: 'user' } })
        fireEvent.change(screen.getByPlaceholderText('Password'), { target: { value: 'wrong' } })
        fireEvent.click(screen.getByRole('button', { name: /sign in/i }))

        // expect error message
        expect(await screen.findByText('Invalid credentials')).toBeInTheDocument()
        expect(onLoginSuccess).not.toHaveBeenCalled()
    })

    // test successful login
    it('calls onLoginSuccess if login succeeds', async () => {
        // mock successful fetch response
        fetch.mockResolvedValueOnce({ ok: true })

        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)
        fireEvent.change(screen.getByPlaceholderText('Username'), { target: { value: 'user' } })
        fireEvent.change(screen.getByPlaceholderText('Password'), { target: { value: 'pass' } })
        fireEvent.click(screen.getByRole('button', { name: /sign in/i }))

        await waitFor(() => {
            expect(onLoginSuccess).toHaveBeenCalled()
        })
    })

    // test loading component while waiting for API
    it('shows loading component while waiting for API', async () => {
        let resolveFetch
        fetch.mockImplementationOnce(() => new Promise(res => { resolveFetch = res }))

        render(<LoginComponent onLoginSuccess={onLoginSuccess}/>)
        fireEvent.change(screen.getByPlaceholderText('Username'), { target: { value: 'user' } })
        fireEvent.change(screen.getByPlaceholderText('Password'), { target: { value: 'pass' } })
        fireEvent.click(screen.getByRole('button', { name: /sign in/i }))

        // expect loading component
        expect(screen.getByTestId('loading')).toBeInTheDocument()

        // resolve fetch
        resolveFetch({ ok: true })
        await waitFor(() => expect(onLoginSuccess).toHaveBeenCalled())
    })
})
