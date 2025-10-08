import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'

// import tested component
import NavbarElement from '../../../src/user-space/elements/NavbarElement'

// mock logout component
vi.mock('../../../src/auth/LogoutComponent', () => ({
    default: () => <button>Logout</button>
}))

describe('NavbarElement', () => {
    const mockUserData = {
        status: 'active',
        username: 'JohnDoe'
    }

    // test render user info
    it('renders user info correctly', () => {
        render(<NavbarElement userData={mockUserData}/>)

        // expect user info
        expect(screen.getByText('JohnDoe')).toBeInTheDocument()
        expect(screen.getByText('(active)')).toBeInTheDocument()
    })

    // test render logout button
    it('renders logout button', () => {
        render(<NavbarElement userData={mockUserData}/>)

        // expect logout button
        expect(screen.getByText('Logout')).toBeInTheDocument()
    })
})
