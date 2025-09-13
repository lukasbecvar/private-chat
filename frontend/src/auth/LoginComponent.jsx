import { useState } from 'react'
import { useConfig } from '../hooks/useConfig.jsx'
import Loading from '../sub-components/LoadingComponent.jsx'

export default function LoginComponent({ onLoginSuccess }) {
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
        <div className="flex flex-col items-center justify-center h-screen auth-component" onKeyDown={handleKeyDown}>
            <div className="w-full max-w-md">
                <div className="bg-white/10 backdrop-blur-lg shadow-lg rounded-lg p-8 space-y-6 border border-gray-600 flex flex-col items-center">
                    <h2 className="text-center text-3xl font-extrabold text-white">
                        Sign in to your account
                    </h2>

                    {error && <div className="bg-red-500/70 text-white p-3 rounded-lg text-center w-full">{error}</div>}

                    <div className="relative w-full">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                            </svg>
                        </span>
                        <input
                            className="caret-white appearance-none block w-full bg-gray-700 text-white border border-gray-600 rounded-lg py-3 px-4 pl-10 leading-tight focus:outline-none focus:bg-gray-700 focus:ring-1 focus:ring-blue-200 hover:border-gray-500"
                            id="username" type="text" placeholder="Username" value={username}
                            onChange={(e) => setUsername(e.target.value)}/>
                    </div>

                    <div className="relative w-full">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
                            </svg>
                        </span>
                        <input
                            className="caret-white appearance-none block w-full bg-gray-700 text-white border border-gray-600 rounded-lg py-3 px-4 pl-10 leading-tight focus:outline-none focus:bg-gray-700 focus:ring-1 focus:ring-blue-200 hover:border-gray-500"
                            id="password" type="password" placeholder="Password" value={password}
                            onChange={(e) => setPassword(e.target.value)}/>
                    </div>

                    <button
                        className="relative w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-600 hover:to-blue-700 overflow-hidden
                                   before:content-[''] before:absolute before:top-0 before:-left-full before:w-full before:h-full before:bg-gradient-to-r before:from-transparent before:via-white before:to-transparent before:opacity-30 before:transform before:skew-x-[-25deg] hover:before:left-full hover:before:transition-all hover:before:duration-700"
                        type="button" onClick={handleLogin} disabled={loading}>
                        Sign In
                    </button>
                </div>
            </div>
        </div>
    )
}
