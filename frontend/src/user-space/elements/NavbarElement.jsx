import LogoutComponent from './../../auth/LogoutComponent'

export default function NavbarElement({ userData }) {
    return (
        <nav className="bg-gray-800 p-2 border-b border-gray-700">
            <div className="flex mx-1 justify-between items-center">
                {/* user info */}
                <div className="text-white flex items-center">
                    <p className="text-2xl mr-2">{userData.username}</p>
                    <p className="text-lg text-gray-400">({userData.status})</p>
                </div>

                {/* logout button */}
                <LogoutComponent/>
            </div>
        </nav>
    )
}
