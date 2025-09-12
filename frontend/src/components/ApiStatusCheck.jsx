import { useEffect, useState } from 'react'
import { useConfig } from '../hooks/UseConfig.jsx'

// import sub-components
import Loading from './sub-components/Loading.jsx'
import ErrorScreen from './sub-components/ErrorScreen.jsx'

export default function ApiStatusCheck({ children }) {
    // init config provider
    const { getConfigValue } = useConfig()
    
    // init states
    const [loading, setLoading] = useState(true)
    const [errorMessage, setErrorMessage] = useState(null)

    // check api status
    useEffect(() => {
        // get config values
        const API_URL = getConfigValue('API_URL')
        const DEV_MODE = getConfigValue('DEV_MODE')
        const API_TIMEOUT_SEC = getConfigValue('API_TIMEOUT_SEC')

        // api fetch method
        const fetchWithTimeout = (url, timeoutSec) => new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(new Error('Timeout')), timeoutSec * 1000)
            fetch(url)
                .then(res => { clearTimeout(timer); resolve(res) })
                .catch(err => { clearTimeout(timer); reject(err) })
        })

        // method for checking api status
        const checkApi = async () => {
            try {
                // send request to api
                const res = await fetchWithTimeout(API_URL, API_TIMEOUT_SEC)
                const data = await res.json()
                // check api response
                if (!res.ok || data.status !== 'success') {
                    const msg = data.message || 'Unknown API error'
                    setErrorMessage(msg)
                    if (DEV_MODE) console.error(`API Error at ${API_URL}: ${msg}`)
                } else {
                    setErrorMessage(null)
                }
            } catch (e) {
                const msg = DEV_MODE 
                    ? `API connection error: failed to connect ${API_URL}, error: ${e.message}` 
                    : 'Unable to connect to the server. Please try again later.'
                setErrorMessage(msg)
                if (DEV_MODE) console.error(`Error connecting to API: ${API_URL}`, e)
            }
            setLoading(false)
        }
        checkApi()
    }, [getConfigValue])

    if (loading) return <Loading/>
    if (errorMessage) return <ErrorScreen errorMessage={errorMessage}/>

    return <>{children}</>
}
