import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/UseConfig.jsx'
import Loading from './sub-components/Loading.jsx'
import ErrorScreen from './sub-components/ErrorScreen.jsx'
import LogoutComponent from './LogoutComponent.jsx'

export default function UserSpace() {
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
    if (error) return <ErrorScreen errorMessage={error}/>

    return (
        <div>
            {/* logout button */}
            <LogoutComponent/>

            {/* user data */}
            <p>Username: {userData.username}</p>
            <p>Status: {userData.status}</p>
        </div>
    )
}
