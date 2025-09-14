import { useEffect, useState } from 'react'
import { useConfig } from './hooks/useConfig'

// import sub-components
import Loading from './sub-components/LoadingComponent'
import ErrorScreenComponent from './sub-components/ErrorScreenComponent'

export default function ApiStatusCheckContainer({ children }) {
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
                setErrorMessage('Unable to connect to the server. Please try again later.')
                if (DEV_MODE) console.error(`Error connecting to API: ${API_URL}`, e.message)
            }
            setLoading(false)
        }
        checkApi()
    }, [getConfigValue])

    // show loading screen
    if (loading) return <Loading/>
    
    // show error screen
    if (errorMessage) return <ErrorScreenComponent errorMessage={errorMessage}/>

    return <>{children}</>
}
