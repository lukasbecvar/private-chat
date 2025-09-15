import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import ErrorScreenComponent from '../../src/sub-components/ErrorScreenComponent'

describe('ErrorScreenComponent', () => {
    let originalLocation

    beforeEach(() => {
        originalLocation = window.location
        delete window.location
        window.location = { reload: vi.fn() }
    })

    afterEach(() => {
        window.location = originalLocation
    })

    it('renders the error message', () => {
        render(<ErrorScreenComponent errorMessage="Test error"/>)
        
        // expect error message
        expect(screen.getByText('Test error')).toBeInTheDocument()
    })

    it('calls reload when clicking reload button', () => {
        render(<ErrorScreenComponent errorMessage="Test error"/>)

        const button = screen.getByRole('button', { name: /reload/i })
        fireEvent.click(button)

        // expect reload
        expect(window.location.reload).toHaveBeenCalled()
    })
})
