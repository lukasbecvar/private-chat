import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/useConfig.jsx'
import Loading from '../sub-components/LoadingComponent.jsx'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent.jsx'
import LogoutComponent from '../auth/LogoutComponent.jsx'

export default function UserSpaceComponent() {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [userData, setUserData] = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    
    // fetch user data
    useEffect(() => {
        const fetchUserData = async () => {
            try {
                const res = await fetch(`${API_URL}/api/user/info`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // get response data
                const data = await res.json()

                // check if response was successful
                if (res.ok && data.status === 'success') {
                    setUserData(data.data)
                } else {
                    setError(data.message || 'Failed to fetch user info')
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error fetching user info: ' + err.message)
                setError('Error fetching user info.')
            } finally {
                setLoading(false)
            }
        }

        fetchUserData()
    }, [DEV_MODE, API_TOKEN, API_URL])

    // show loading screen
    if (loading) return <Loading/>

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    return (
        <div>
            {/* nav-bar */}
            <nav className="bg-gray-800 p-2">
                <div className="flex mx-1 justify-between items-center">
                    <div className="text-white flex items-center">
                        <p className="text-2xl mr-2">{userData.username}</p>
                        <p className="text-lg text-gray-400">({userData.status})</p>
                    </div>
                    <LogoutComponent/>
                </div>
            </nav>

            {/* example content */}
            <div className="p-2">
                <p>testing msg</p>
            </div>
        </div>
    )
}
