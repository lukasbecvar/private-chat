import { describe, it, expect } from 'vitest'
import { render } from '@testing-library/react'
import Loading from '../../src/sub-components/LoadingComponent'

describe('LoadingComponent', () => {
    // test render loading spinner
    it('renders the loading spinner', () => {
        const { container } = render(<Loading/>)
        const spinnerDiv = container.querySelector('.gradient-spinner')
        expect(spinnerDiv).toBeTruthy()
    })
})
