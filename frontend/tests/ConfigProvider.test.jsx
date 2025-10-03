import { render, screen } from '@testing-library/react'
import { describe, it, expect, vi, afterEach } from 'vitest'
import { ConfigProvider, ConfigContext } from '../src/ConfigProvider'

// mock sub-components
vi.mock('../src/sub-components/LoadingComponent', () => ({
    default: () => <div>Loading...</div>
}))
vi.mock('../src/sub-components/ErrorScreenComponent', () => ({
    default: ({ errorMessage }) => <div>Error: {errorMessage}</div>
}))

// mock fetch
globalThis.fetch = vi.fn()

describe('ConfigProvider', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    // test render loading component
    it('shows loading initially', () => {
        fetch.mockImplementation(() => new Promise(() => { }))
        render(
            <ConfigProvider>
                <div>Child Content</div>
            </ConfigProvider>
        )

        // expect loading
        expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
    })

    // test render children when config is loaded
    it('renders children when config is loaded', async () => {
        const mockConfig = { API_URL: 'http://mock-api', DEV_MODE: true }
        fetch.mockResolvedValue({
            ok: true,
            json: async () => mockConfig
        })
        let contextValue
        render(
            <ConfigProvider>
                <ConfigContext.Consumer>
                    {(value) => {
                        contextValue = value
                        return <div>Child Content</div>
                    }}
                </ConfigContext.Consumer>
            </ConfigProvider>
        )

        // expect children
        expect(await screen.findByText(/Child Content/i)).toBeInTheDocument()

        // expect context value
        expect(contextValue.getConfigValue('API_URL')).toBe('http://mock-api')
    })

    // test render error screen component if fetch returns error
    it('renders error screen if fetch fails', async () => {
        fetch.mockRejectedValue(new Error('Network fail'))
        render(
            <ConfigProvider>
                <div>Child Content</div>
            </ConfigProvider>
        )

        // expect error screen
        expect(await screen.findByText(/Error: Configuration file load error: Network fail/i)).toBeInTheDocument()
    })

    // test render error screen component if fetch returns non-ok response
    it('renders error screen if fetch returns non-ok response', async () => {
        fetch.mockResolvedValue({
            ok: false,
            status: 404,
            json: async () => ({})
        })
        render(
            <ConfigProvider>
                <div>Child Content</div>
            </ConfigProvider>
        )

        // expect error screen
        expect(await screen.findByText(/Error: Configuration file load error: HTTP 404/i)).toBeInTheDocument()
    })
})
