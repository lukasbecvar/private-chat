import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import LogoutComponent from '../../src/auth/LogoutComponent.jsx'

// mock app config
vi.mock('../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const mockConfig = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: true
            }
            return mockConfig[key]
        }
    })
}))

// mock error screen component
vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div data-testid="error-screen">{errorMessage}</div>
}))

describe('LogoutComponent', () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn()
        vi.restoreAllMocks()
    })

    it('renders logout button', () => {
        render(<LogoutComponent/>)

        // expect logout button
        expect(screen.getByRole('button', { name: /logout/i })).toBeInTheDocument()
    })

    it('shows loading text when logout is in progress', async () => {
        let resolveFetch
        fetch.mockImplementationOnce(() => new Promise(res => { resolveFetch = res }))

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByRole('button', { name: /logout/i }))

        expect(screen.getByRole('button', { name: /logging out.../i })).toBeInTheDocument()

        // resolve fetch
        resolveFetch({ ok: true })
    })

    it('calls reload on successful logout', async () => {
        const originalLocation = window.location
        delete window.location
        window.location = { reload: vi.fn() }

        fetch.mockResolvedValueOnce({ ok: true })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByRole('button', { name: /logout/i }))

        // expect reload
        await waitFor(() => expect(window.location.reload).toHaveBeenCalled())
        window.location = originalLocation
    })

    it('shows error screen if logout fails with message', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({ message: 'Logout failed due to server error' })
        })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByRole('button', { name: /logout/i }))

        // expect error screen
        expect(await screen.findByTestId('error-screen')).toHaveTextContent(
            'Logout failed due to server error'
        )
    })

    it('shows generic error screen if logout fails without message', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({})
        })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByRole('button', { name: /logout/i }))

        // expect error screen
        expect(await screen.findByTestId('error-screen')).toHaveTextContent('Logout failed')
    })
})
