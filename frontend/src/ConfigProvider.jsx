import { createContext, useEffect, useState } from 'react'

// import sub-components
import Loading from './sub-components/LoadingComponent'
import ErrorScreenComponent from './sub-components/ErrorScreenComponent'

// create config context
const ConfigContext = createContext({})

export const ConfigProvider = ({ children }) => {
    // states
    const [config, setConfig] = useState(null)
    const [error, setError] = useState(null)

    // method for self fetching config file
    useEffect(() => {
        fetch('/config.json')
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`)
                return res.json()
            })
            .then(cfg => setConfig(cfg))
            .catch(err => {
                console.error('Failed to load config.json', err)
                setError(err)
            })
    }, [])

    // show error screen if config load failed
    if (error) {
        return <ErrorScreenComponent errorMessage={'Configuration file load error: ' + error.message}/>
    }

    // show loading component if config is still loading
    if (!config) {
        return <Loading/>
    }

    // helper for getting config values
    const getConfigValue = (key) => config[key]

    return (
        <ConfigContext.Provider value={{ config, getConfigValue }}>
            {children}
        </ConfigContext.Provider>
    )
}

// hook for using config in components
export { ConfigContext }
