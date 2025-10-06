import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/useConfig'

// import app components
import LoginComponent from './LoginComponent'
import Loading from '../sub-components/LoadingComponent'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent'

export default function TokenCheckContainer({ children }) {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [message, setMessage] = useState('')
    const [status, setStatus] = useState('checking')

    // check if user is authenticated
    useEffect(() => {
        const verifyUserStatus = async () => {
            try {
                const res = await fetch(`${API_URL}/api/user/info`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-TOKEN': API_TOKEN
                    }
                })

                // check if user is authenticated
                if (res.ok) {
                    setStatus('authenticated')
                    return
                }

                // handle errors
                let errorData = null
                try {
                    errorData = await res.json()
                } catch (e) {
                    if (DEV_MODE) console.error('Error to get error data from response: ' + e.message)
                    setMessage('An unexpected error occurred on the server.')
                    setStatus('error')
                    return
                }

                // check if user is unauthenticated
                if (res.status === 401) {
                    setStatus('unauthenticated')
                } else {
                    setStatus('error')
                    setMessage(errorData.message || 'An unknown error occurred.')
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error to verify user status: ' + err.message)
                setMessage('User status verification error: ' + err.message)
                setStatus('error')
            }
        }

        verifyUserStatus()
    }, [DEV_MODE, API_TOKEN, API_URL])


    // show loading screen
    if (status === 'checking') return <Loading/>

    // show auth component
    if (status === 'unauthenticated') {
        return (
            <LoginComponent
                onLoginSuccess={() => {
                    setStatus('authenticated')
                }}
            />
        )
    }

    // show error screen
    if (status === 'error') return <ErrorScreenComponent errorMessage={message}/>

    // show children if user is authenticated
    if (status === 'authenticated') return <main>{children}</main>

    return null
}
