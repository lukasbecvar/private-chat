import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import NavbarElement from '../../../src/user-space/elements/NavbarElement'

// mock logout component
vi.mock('../../../src/auth/LogoutComponent', () => ({
    default: () => <button>Logout</button>
}))

describe('NavbarElement', () => {
    const mockUserData = {
        username: 'JohnDoe',
        status: 'online'
    }

    it('renders user info correctly', () => {
        render(<NavbarElement userData={mockUserData}/>)

        // expect user info
        expect(screen.getByText('JohnDoe')).toBeInTheDocument()
        expect(screen.getByText('(online)')).toBeInTheDocument()
    })

    it('renders logout button', () => {
        render(<NavbarElement userData={mockUserData}/>)

        // expect logout button
        expect(screen.getByText('Logout')).toBeInTheDocument()
    })
})
