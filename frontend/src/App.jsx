import { ConfigProvider } from './ConfigProvider'
import { useState, useEffect, useCallback } from 'react'

// import app components
import TokenCheckContainer from './auth/TokenCheckContainer'
import ApiStatusCheckContainer from './ApiStatusCheckContainer'
import UserSpaceComponent from './user-space/UserSpaceComponent'
import ErrorScreenComponent from './sub-components/ErrorScreenComponent'

// import styles
import './assets/css/index.scss'

export default function App() {
    const [isUnsupportedResolution, setIsUnsupportedResolution] = useState(false)

    // check resolution on window resize
    const checkResolution = useCallback(() => {
        if (window.innerWidth < 222) {
            setIsUnsupportedResolution(true)
        } else {
            setIsUnsupportedResolution(false)
        }
    }, [])

    // window resize event listener
    useEffect(() => {
        checkResolution()
        window.addEventListener('resize', checkResolution)

        return () => {
            window.removeEventListener('resize', checkResolution)
        }
    }, [checkResolution])

    // show error screen if resolution is too low
    if (isUnsupportedResolution) {
        return <ErrorScreenComponent errorMessage={'Your device has an unsupported resolution. Please use a device with a larger screen.'} />
    }

    return (
        <ConfigProvider>
            <ApiStatusCheckContainer>
                <TokenCheckContainer>
                    <UserSpaceComponent/>
                </TokenCheckContainer>
            </ApiStatusCheckContainer>
        </ConfigProvider>
    )
}
