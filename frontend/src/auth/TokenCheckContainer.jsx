// src/components/TokenCheckContainer.jsx
import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/useConfig.jsx'
import UserSpaceComponent from '../user-space/UserSpaceComponent.jsx'
import LoginComponent from './LoginComponent.jsx'
import Loading from '../sub-components/LoadingComponent.jsx'
import ErrorScreenComponent from '../sub-components/ErrorScreenComponent.jsx'

export default function TokenCheckContainer() {
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

    // show user space
    if (status === 'authenticated') {
        return (
            <UserSpaceComponent/>
        )
    }

    return null
}
