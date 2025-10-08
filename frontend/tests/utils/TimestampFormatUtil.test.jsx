import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// import tested util
import { TimestampFormatUtil } from '../../src/utils/TimestampFormatUtil'

describe('TimestampFormatUtil', () => {
    let RealDate

    beforeEach(() => {
        RealDate = Date
    })

    afterEach(() => {
        globalThis.Date = RealDate
        vi.restoreAllMocks()
    })

    // test format today timestamps
    it('formats timestamps from today correctly', () => {
        const fixedNow = new RealDate('2025-09-24T15:30:00')
        class MockDate extends RealDate {
            constructor(arg) {
                if (arg !== undefined) {
                    return new RealDate(arg)
                }
                return fixedNow
            }
        }
        globalThis.Date = MockDate
        const timestamp = new RealDate('2025-09-24T08:15:00').getTime()
        const result = TimestampFormatUtil(timestamp)
        
        // expect result
        expect(result).toBe('08:15')
    })

    // test format yesterday timestamps
    it('formats timestamps from yesterday correctly', () => {
        const fixedNow = new RealDate('2025-09-24T15:30:00')
        class MockDate extends RealDate {
            constructor(arg) {
                if (arg !== undefined) {
                    return new RealDate(arg)
                }
                return fixedNow
            }
        }
        globalThis.Date = MockDate
        const timestamp = new RealDate('2025-09-23T20:45:00').getTime()
        const result = TimestampFormatUtil(timestamp)
        
        // expect result
        expect(result).toBe('Yesterday at 20:45')
    })

    // test format older timestamps
    it('formats older timestamps correctly', () => {
        const fixedNow = new RealDate('2025-09-24T15:30:00')
        class MockDate extends RealDate {
            constructor(arg) {
                if (arg !== undefined) {
                    return new RealDate(arg)
                }
                return fixedNow
            }
        }
        globalThis.Date = MockDate
        const timestamp = new RealDate('2025-09-20T10:05:00').getTime()
        const result = TimestampFormatUtil(timestamp)
        
        // test result
        expect(result).toBe('20/09/2025 10:05')
    })
})
