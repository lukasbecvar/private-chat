// tests/hooks/useConfig.test.jsx
import { renderHook } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { useConfig } from '../../src/hooks/useConfig'
import { ConfigContext } from '../../src/ConfigProvider'

describe('useConfig hook', () => {
  it('returns values from ConfigContext', () => {
    const mockConfig = {
      API_URL: 'http://mock-api',
      API_TOKEN: 'mock-token',
      DEV_MODE: true
    }

    const wrapper = ({ children }) => (
      <ConfigContext.Provider value={{ getConfigValue: (key) => mockConfig[key] }}>
        {children}
      </ConfigContext.Provider>
    )

    const { result } = renderHook(() => useConfig(), { wrapper })

    expect(result.current.getConfigValue('API_URL')).toBe('http://mock-api')
    expect(result.current.getConfigValue('API_TOKEN')).toBe('mock-token')
    expect(result.current.getConfigValue('DEV_MODE')).toBe(true)
  })
})
