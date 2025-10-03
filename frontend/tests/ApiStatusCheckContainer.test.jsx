import { render, screen } from '@testing-library/react'
import { describe, it, expect, vi, afterEach } from 'vitest'
import ApiStatusCheckContainer from '../src/ApiStatusCheckContainer'

// mock app config
vi.mock('../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                DEV_MODE: true,
                API_TIMEOUT_SEC: 1
            }
            return config[key]
        }
    })
}))

// mock sub-components
vi.mock('../src/sub-components/LoadingComponent', () => ({
    default: () => <div>Loading...</div>
}))
vi.mock('../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock fetch
globalThis.fetch = vi.fn()

describe('ApiStatusCheckContainer', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    // test render loading component
    it('shows loading initially', () => {
        fetch.mockImplementation(() => new Promise(() => { }))
        render(
            <ApiStatusCheckContainer>
                <div>Child Content</div>
            </ApiStatusCheckContainer>
        )

        // expect loading
        expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
    })

    // test render children if API is OK
    it('renders children if API is OK', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'success' })
        })
        render(
            <ApiStatusCheckContainer>
                <div>Child Content</div>
            </ApiStatusCheckContainer>
        )

        // expect children
        expect(await screen.findByText(/Child Content/i)).toBeInTheDocument()
    })

    // test render error screen component if API returns error
    it('renders ErrorScreenComponent if API returns error', async () => {
        fetch.mockResolvedValue({
            ok: false,
            json: async () => ({ status: 'fail', message: 'API failed' })
        })
        render(
            <ApiStatusCheckContainer>
                <div>Child Content</div>
            </ApiStatusCheckContainer>
        )
        
        // expect error screen
        expect(await screen.findByText(/Error: API failed/i)).toBeInTheDocument()
    })

    // test render generic error if fetch throws
    it('renders generic error if fetch throws', async () => {
        fetch.mockRejectedValue(new Error('Network fail'))
        render(
            <ApiStatusCheckContainer>
                <div>Child Content</div>
            </ApiStatusCheckContainer>
        )

        // expect error screen
        expect(await screen.findByText(/Error: Unable to connect to the server/i)).toBeInTheDocument()
    })

    // test render generic error if API returns unexpected status
    it('renders generic error if API returns unexpected status', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({ status: 'error', message: 'Bad API response' })
        })
        render(
            <ApiStatusCheckContainer>
                <div>Child Content</div>
            </ApiStatusCheckContainer>
        )

        // expect error screen
        expect(await screen.findByText(/Error: Bad API response/i)).toBeInTheDocument()
    })
})
