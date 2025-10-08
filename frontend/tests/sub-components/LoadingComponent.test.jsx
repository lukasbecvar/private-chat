import { describe, it, expect } from 'vitest'
import { render } from '@testing-library/react'

// import tested component
import Loading from '../../src/sub-components/LoadingComponent'

describe('LoadingComponent', () => {
    // test render loading spinner
    it('renders the loading spinner', () => {
        const { container } = render(<Loading/>)
        const spinnerDiv = container.querySelector('.gradient-spinner')
       
        // expect spinner div to be rendered
        expect(spinnerDiv).toBeTruthy()
    })
})
