// src/components/JWTCheck.jsx
import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/UseConfig.jsx'
import UserSpace from './UserSpace.jsx'
import AuthComponent from './AuthComponent.jsx'
import Loading from './sub-components/Loading.jsx'
import ErrorScreen from './sub-components/ErrorScreen.jsx'

export default function JWTCheck() {
    // get config values
    const { getConfigValue } = useConfig()
    const API_URL = getConfigValue('API_URL')
    const DEV_MODE = getConfigValue('DEV_MODE')
    const API_TOKEN = getConfigValue('API_TOKEN')

    // states
    const [status, setStatus] = useState('checking')
    const [message, setMessage] = useState('')

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

                if (res.ok) {
                    setStatus('authenticated')
                    return
                }

                let errorData = null
                try {
                    errorData = await res.json()
                } catch (e) {
                    if (DEV_MODE) console.error('Error to get error data from response: ' + e.message)
                    setMessage('An unexpected error occurred on the server.')
                    setStatus('error')
                    return
                }

                if (res.status === 401) {
                    setStatus('unauthenticated')
                } else {
                    setStatus('error')
                    setMessage(errorData.message || 'An unknown error occurred.')
                }
            } catch (err) {
                if (DEV_MODE) console.error('Error to verify user status: ' + err.message)
                setMessage(err.message)
                setStatus('error')
            }
        }

        verifyUserStatus()
    }, [API_URL, API_TOKEN, DEV_MODE])


    // show loading screen
    if (status === 'checking') return <Loading/>

    // show auth component
    if (status === 'unauthenticated') {
        return (
            <AuthComponent
                onLoginSuccess={() => {
                    setStatus('authenticated')
                }}
            />
        )
    }

    // show error screen
    if (status === 'error') return <ErrorScreen errorMessage={message}/>

    // show user space
    if (status === 'authenticated') {
        return (
            <UserSpace/>
        )
    }

    return null
}
