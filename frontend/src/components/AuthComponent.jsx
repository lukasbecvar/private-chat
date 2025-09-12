import { useState } from 'react'
import { useConfig } from '../hooks/UseConfig.jsx'
import Loading from './sub-components/Loading.jsx'

export default function AuthComponent({ onLoginSuccess }) {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [username, setUsername] = useState('')
    const [password, setPassword] = useState('')
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState(null)

    // submit login form on enter key
    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            handleLogin()
        }
    }

    // handle login process
    const handleLogin = async () => {
        setLoading(true)
        setError(null)
        try {
            const res = await fetch(`${API_URL}/api/auth/login`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_TOKEN
                },
                body: JSON.stringify({ username, password })
            })

            // check if login was successful
            if (res.ok) {
                onLoginSuccess()
            } else {
                const data = await res.json()
                let message = data.message || data.error || 'Login failed'
                setError(message)
                if (DEV_MODE) console.error(`Login error: ${message}`)
            }
        } catch (err) {
            setError(err.message)
        } finally {
            setLoading(false)
        }
    }

    // show loading screen
    if (loading) return <Loading/>

    return (
        <div onKeyDown={handleKeyDown}>
            {/* error message box */}
            {error && <div>{error}</div>}
            
            {/* form inputs */}
            <input type="text" placeholder="Username" value={username} onChange={(e) => setUsername(e.target.value)}/>
            <input type="password" placeholder="Password" value={password} onChange={(e) => setPassword(e.target.value)}/>
            
            {/* submit buttons */}
            <button onClick={handleLogin} disabled={loading}>
                Login
            </button>
        </div>
    )
}
