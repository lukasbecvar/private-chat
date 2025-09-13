import { useState } from 'react'
import { useConfig } from '../hooks/useConfig.jsx'
import Loading from '../sub-components/LoadingComponent.jsx'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent.jsx'

export default function LogoutComponent() {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')
    
    // states
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    const handleLogout = async () => {
        setLoading(true)
        setError(null)

        try {
            const res = await fetch(`${API_URL}/api/auth/logout`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                }
            })

            // check if logout was successful
            if (!res.ok) {
                try {
                    const data = await res.json()
                    setError(data.message || 'Logout failed')
                } catch (e) {
                    if (DEV_MODE) console.error('Error to get error data from response: ' + e.message)
                    setError('Logout failed with non-JSON response.')
                }
            }
        } catch (err) {
            setError('Error logging out: ' + err.message)
        } finally {
            window.location.reload()
        }
    }

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    return (
        <button
            className="bg-red-500 hover:bg-red-700 text-white font-bold text-base py-1.5 px-4 rounded focus:outline-none focus:shadow-outline"
            onClick={handleLogout} disabled={loading}>
            {loading ? 'Logging out...' : 'Logout'}
        </button>
    )
}
