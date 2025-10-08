import { render, screen } from '@testing-library/react'
import { describe, it, vi, afterEach, expect } from 'vitest'

// import tested component
import App from '../src/App'

// mock react-dom/client
vi.mock('react-dom/client', async () => {
    const actual = await vi.importActual('react-dom/client')
    return {
        ...actual,
        createRoot: () => ({
            render: (comp) => comp
        })
    }
})

// mock app components
vi.mock('../src/ConfigProvider', () => ({
    ConfigProvider: ({ children }) => <div data-testid="config-provider">{children}</div>
}))
vi.mock('../src/ApiStatusCheckContainer', () => ({
    default: ({ children }) => <div data-testid="api-check">{children}</div>
}))
vi.mock('../src/auth/TokenCheckContainer', () => ({
    default: ({ children }) => <div data-testid="token-check">{children}</div>
}))
vi.mock('../src/user-space/UserSpaceComponent', () => ({
    default: () => <div data-testid="user-space">User Space Content</div>
}))

describe('App', () => {
    afterEach(() => {
        vi.clearAllMocks()
    })

    // test render app with all required containers
    it('renders the app with all containers', () => {
        render(<App/>)

        // expect all containers
        expect(screen.getByTestId('config-provider')).toBeInTheDocument()
        expect(screen.getByTestId('api-check')).toBeInTheDocument()
        expect(screen.getByTestId('token-check')).toBeInTheDocument()
        expect(screen.getByTestId('user-space')).toBeInTheDocument()
    })
})
