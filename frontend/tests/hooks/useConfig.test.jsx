import { describe, it, expect } from 'vitest'
import { renderHook } from '@testing-library/react'
import { useConfig } from '../../src/hooks/useConfig'

// import tested config hook
import { ConfigContext } from '../../src/ConfigProvider'

describe('useConfig hook', () => {
    // test hook returns values from config
    it('returns values from ConfigContext', () => {
        // mock config values
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

        // expect values
        expect(result.current.getConfigValue('API_URL')).toBe('http://mock-api')
        expect(result.current.getConfigValue('API_TOKEN')).toBe('mock-token')
        expect(result.current.getConfigValue('DEV_MODE')).toBe(true)
    })
})
