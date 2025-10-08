import { describe, it, expect } from 'vitest'
import { render } from '@testing-library/react'

// import tested component
import BouncingLoader from '../../src/sub-components/BouncingLoader'

describe('BouncingLoader', () => {
    // test render bouncing loader
    it('renders the bouncing loader', () => {
        const { container } = render(<BouncingLoader/>)
        const loaderDiv = container.querySelector('.bouncing-loader')

        // expect loader div
        expect(loaderDiv).toBeTruthy()
        expect(loaderDiv.children.length).toBe(3)
    })
})
