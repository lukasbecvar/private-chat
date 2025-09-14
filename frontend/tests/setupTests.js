// tests/setup.js
import '@testing-library/jest-dom'
import { vi, beforeEach, afterEach } from 'vitest'

// suppress console.error/warn globally
beforeEach(() => {
  vi.spyOn(console, 'error').mockImplementation(() => {})
  vi.spyOn(console, 'warn').mockImplementation(() => {})
})

afterEach(() => {
  vi.restoreAllMocks()
})

// mock window.location methods
Object.defineProperty(window, 'location', {
  writable: true,
  value: {
    assign: vi.fn(),
    replace: vi.fn(),
    reload: vi.fn()
  },
})

// mock global fetch to avoid unhandled promise rejections
if (!globalThis.fetch) {
  globalThis.fetch = vi.fn(() => Promise.resolve({
    ok: true,
    json: async () => ({ status: 'success' })
  }))
}
