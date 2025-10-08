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
    
    // reset mocks
    beforeEach(() => {
        globalThis.fetch = vi.fn()
        vi.restoreAllMocks()
    })

    // test render logout component
    it('renders logout button', () => {
        render(<LogoutComponent/>)

        // expect logout button
        expect(screen.getByTestId('logout-button')).toBeInTheDocument()
    })

    // test disable logout button
    it('disable logout button while loading', () => {
        // mock fetch to never resolve
        global.fetch = vi.fn(() => new Promise(() => {}))

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByTestId('logout-button'))

        // expect loading text
        expect(screen.getByTestId('logout-button')).toBeDisabled()
    })

    // test logout on click
    it('calls reload on successful logout', async () => {
        const originalLocation = window.location
        delete window.location
        window.location = { reload: vi.fn() }

        fetch.mockResolvedValueOnce({ ok: true })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByTestId('logout-button'))

        // expect reload
        await waitFor(() => expect(window.location.reload).toHaveBeenCalled())
        window.location = originalLocation
    })

    // test error screen on logout failure
    it('shows error screen if logout fails with message', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({ message: 'Logout failed due to server error' })
        })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByTestId('logout-button'))

        // expect error screen
        expect(await screen.findByTestId('error-screen')).toHaveTextContent(
            'Logout failed due to server error'
        )

        consoleErrorSpy.mockRestore();
    })

    // test error screen on logout failure
    it('shows generic error screen if logout fails without message', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({})
        })

        render(<LogoutComponent/>)
        fireEvent.click(screen.getByTestId('logout-button'))

        // expect error screen
        expect(await screen.findByTestId('error-screen')).toHaveTextContent('Logout failed')

        consoleErrorSpy.mockRestore();
    })
})
