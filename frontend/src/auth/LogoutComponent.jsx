import { useState } from 'react'
import { useConfig } from '../hooks/useConfig'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faSignOut, faSpinner } from '@fortawesome/free-solid-svg-icons'

export default function LogoutComponent() {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(false)

    // user logout process
    const handleLogout = async () => {
        // default process state
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
                    // get response data
                    const data = await res.json()

                    // handle error
                    if (DEV_MODE) console.error(`Logout error: ${data.message}`)
                    setError(data.message || 'Logout failed')
                } catch (e) {
                    if (DEV_MODE) console.error(`Error to get error data from response: ${e.message}`)
                    setError('Logout failed.')
                }
            }
        } catch (err) {
            if (DEV_MODE) console.error('Error logging out: ' + err.message)
            setError('Logout failed.')
        } finally {
            // reload the page if logout was successful
            if (error === null) window.location.reload()
        }
    }

    // show error screen
    if (error) return <ErrorScreenComponent errorMessage={error}/>

    return (
        <button className="bg-red-500 hover:bg-red-400 text-white font-extrabold text-base py-1 px-2 rounded focus:outline-none focus:shadow-outline"
            onClick={handleLogout} disabled={loading} data-testid="logout-button">
            {loading ? (
                <FontAwesomeIcon icon={faSpinner} spin/>
            ) : (
                <FontAwesomeIcon icon={faSignOut}/>
            )}
        </button>
    )
}
