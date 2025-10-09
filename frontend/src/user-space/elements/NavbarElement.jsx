import LogoutComponent from './../../auth/LogoutComponent'
import defaultProfileImage from '../../assets/images/default-profile-image.png'

export default function NavbarElement({ userData }) {
    return (
        <nav className="bg-gray-800/70 p-2 border-b border-gray-700/50 shadow-sm">
            <div className="flex justify-between items-center">
                {/* user info */}
                <div className="text-white flex items-center">
                    <img 
                        alt="profile" 
                        className="w-11 h-11 rounded-full mr-3 border-2 border-gray-600"
                        src={userData['profile-picture'] === 'nonset-default' ? defaultProfileImage : userData['profile-picture']}
                    />
                    <div>
                        <p className="text-lg font-semibold">{userData.username}</p>
                        <p className="text-xs text-gray-400">{userData.status}</p>
                    </div>
                </div>

                {/* logout button */}
                <LogoutComponent/>
            </div>
        </nav>
    )
}
