import { ConfigProvider } from './ConfigProvider'

// import app components
import TokenCheckContainer from './auth/TokenCheckContainer'
import ApiStatusCheckContainer from './ApiStatusCheckContainer'
import UserSpaceComponent from './user-space/UserSpaceComponent'

// import styles
import './assets/css/index.scss'

export default function App() {
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
