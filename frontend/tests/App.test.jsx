// tests/App.test.jsx
import { describe, it, vi, afterEach, expect } from 'vitest'
import { render, screen } from '@testing-library/react'

// mock ReactDOM.createRoot to avoid actual DOM rendering
vi.mock('react-dom/client', async () => {
  const actual = await vi.importActual('react-dom/client')
  return {
    ...actual,
    createRoot: () => ({
      render: (comp) => comp, // just return component, don't touch DOM
    }),
  }
})

// mock heavy components
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

import App from '../src/App'

describe('App', () => {
  afterEach(() => {
    vi.clearAllMocks()
  })

  it('renders the app with all containers', () => {
    render(<App />)
    expect(screen.getByTestId('config-provider')).toBeInTheDocument()
    expect(screen.getByTestId('api-check')).toBeInTheDocument()
    expect(screen.getByTestId('token-check')).toBeInTheDocument()
    expect(screen.getByTestId('user-space')).toBeInTheDocument()
  })
})
