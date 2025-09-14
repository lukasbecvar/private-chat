// tests/sub-components/ErrorScreenComponent.test.jsx
import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import ErrorScreenComponent from '../../src/sub-components/ErrorScreenComponent'

describe('ErrorScreenComponent', () => {
  let originalLocation

  beforeEach(() => {
    // uložíme původní location
    originalLocation = window.location
    delete window.location
    window.location = { reload: vi.fn() } // nahradíme reload mockem
  })

  afterEach(() => {
    // obnovíme původní location
    window.location = originalLocation
  })

  it('renders the error message', () => {
    render(<ErrorScreenComponent errorMessage="Test error" />)
    expect(screen.getByText('Test error')).toBeInTheDocument()
  })

  it('calls window.location.reload when clicking reload button', () => {
    render(<ErrorScreenComponent errorMessage="Test error" />)
    
    const button = screen.getByRole('button', { name: /reload/i })
    fireEvent.click(button)

    expect(window.location.reload).toHaveBeenCalled()
  })
})
