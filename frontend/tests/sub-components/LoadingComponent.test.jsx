// tests/sub-components/LoadingComponent.test.jsx
import { render } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import Loading from '../../src/sub-components/LoadingComponent'

describe('LoadingComponent', () => {
  it('renders the loading spinner', () => {
    const { container } = render(<Loading />)
    const spinnerDiv = container.querySelector('.gradient-spinner')
    expect(spinnerDiv).toBeTruthy()
  })
})
