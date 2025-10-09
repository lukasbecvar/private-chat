import { useState } from 'react'
import { useConfig } from '../hooks/useConfig'
import LoadingComponent from '../sub-components/LoadingComponent'

// import fontawesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faUser, faLock, faSignInAlt } from '@fortawesome/free-solid-svg-icons'

export default function LoginComponent({ onLoginSuccess }) {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [error, setError] = useState(null)
    const [loading, setLoading] = useState(false)
    
    // form input states
    const [username, setUsername] = useState('')
    const [password, setPassword] = useState('')

    // submit login form on press enter
    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            handleLogin()
        }
    }

    // handle login process
    const handleLogin = async () => {

        // check if username is empty
        if (username === '') {
            setError('Username cannot be empty.')
            return
        }

        // check if password is empty
        if (password === '') {
            setError('Password cannot be empty.')
            return
        }

        // default process state
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
                // get response message
                const data = await res.json()
                let message = data.message || data.error || 'Login failed'
                
                // handle error
                if (DEV_MODE) console.error(`Login error: ${message}`)
                setError(message)
            }
        } catch (err) {
            if (DEV_MODE) console.error(`Error logging in: ${err.message}`)
            setError('Error logging in.')
        } finally {
            setLoading(false)
        }
    }

    // show loading screen
    if (loading) return <LoadingComponent/>

    return (
        <div className="flex flex-col items-center justify-center h-screen auth-component p-2 bg-gradient-to-br from-gray-900 to-gray-800" onKeyDown={handleKeyDown}>
            <div className="w-full max-w-md">
                <div className="bg-white/5 backdrop-blur-xl shadow-2xl rounded-xl p-6 md:p-8 space-y-6 border border-gray-700 flex flex-col items-center">
                    <h2 className="text-center text-3xl font-extrabold text-white">
                        Sign in to your account
                    </h2>

                    {/* error message box */}
                    {error && <div className="bg-red-600/70 text-white p-3 rounded-md text-center w-full">{error}</div>}

                    {/* username input */}
                    <div className="relative w-full">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                            <FontAwesomeIcon icon={faUser} className="text-gray-400"/>
                        </span>
                        <input
                            className="caret-white appearance-none block w-full bg-gray-800/50 text-white border border-gray-700 rounded-lg py-3 px-4 pl-10 leading-tight focus:outline-none focus:bg-gray-800/70 focus:ring-2 focus:ring-blue-500/50 hover:border-gray-600 transition-all duration-300"
                            id="username" type="text" placeholder="Username" value={username}
                            onChange={(e) => setUsername(e.target.value)}
                        />
                    </div>

                    {/* password input */}
                    <div className="relative w-full">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                            <FontAwesomeIcon icon={faLock} className="text-gray-400" />
                        </span>
                        <input
                            className="caret-white appearance-none block w-full bg-gray-800/50 text-white border border-gray-700 rounded-lg py-3 px-4 pl-10 leading-tight focus:outline-none focus:bg-gray-800/70 focus:ring-2 focus:ring-blue-500/50 hover:border-gray-600 transition-all duration-300"
                            id="password" type="password" placeholder="Password" value={password}
                            onChange={(e) => setPassword(e.target.value)}
                        />
                    </div>

                    {/* form submit button */}
                    <button
                        className="relative w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 group"
                        onClick={handleLogin} disabled={loading}
                        type="button"
                    >
                        <span className="absolute left-0 inset-y-0 flex items-center pl-3">
                            <FontAwesomeIcon icon={faSignInAlt} className="text-blue-100 group-hover:text-blue-200"/>
                        </span>
                        Sign In
                    </button>
                </div>
            </div>
        </div>
    )
}
