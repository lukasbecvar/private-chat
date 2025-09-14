// tests/auth/TokenCheckContainer.test.jsx
import { render, screen } from '@testing-library/react'
import TokenCheckContainer from '../../src/auth/TokenCheckContainer'
import { vi, afterEach, describe, it, expect } from 'vitest'

// --- Mock hooks ---
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

// --- Mock components ---
vi.mock('../../src/auth/LoginComponent', () => ({
  default: () => <div>Sign in to your account</div>
}))

vi.mock('../../src/sub-components/LoadingComponent', () => ({
  default: () => <div>Loading...</div>
}))

vi.mock('../../src/sub-components/ErrorScreenComponent', () => ({
  default: ({ errorMessage }) => <div>{errorMessage}</div>
}))


// --- Mock fetch globally ---
globalThis.fetch = vi.fn()

describe('TokenCheckContainer', () => {

  afterEach(() => {
    vi.clearAllMocks()
  })

  it('shows loading initially', async () => {
    // simulate fetch never resolves → loading
    fetch.mockImplementation(() => new Promise(() => {}))
    render(
      <TokenCheckContainer>
        <div>Child Content</div>
      </TokenCheckContainer>
    )
    expect(screen.getByText(/Loading.../i)).toBeInTheDocument()
  })

  it('renders children if authenticated', async () => {
    fetch.mockResolvedValue({
      ok: true
    })
    render(
      <TokenCheckContainer>
        <div>Child Content</div>
      </TokenCheckContainer>
    )
    expect(await screen.findByText('Child Content')).toBeInTheDocument()
  })

  it('renders LoginComponent if unauthenticated', async () => {
    fetch.mockResolvedValue({
      ok: false,
      status: 401,
      json: async () => ({ message: 'Unauthorized' })
    })
    render(
      <TokenCheckContainer>
        <div>Child Content</div>
      </TokenCheckContainer>
    )
    expect(await screen.findByText(/Sign in to your account/i)).toBeInTheDocument()
  })

  it('renders ErrorScreenComponent if server returns error message', async () => {
    fetch.mockResolvedValue({
      ok: false,
      status: 500,
      json: async () => ({ message: 'Server failed' })
    })
    render(
      <TokenCheckContainer>
        <div>Child Content</div>
      </TokenCheckContainer>
    )
    expect(await screen.findByText(/Server failed/i)).toBeInTheDocument()
  })

  it('renders generic error if fetch throws', async () => {
    fetch.mockRejectedValue(new Error('Network fail'))
    render(
      <TokenCheckContainer>
        <div>Child Content</div>
      </TokenCheckContainer>
    )
    expect(await screen.findByText(/Network fail/i)).toBeInTheDocument()
  })
})
