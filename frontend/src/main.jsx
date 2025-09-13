import ReactDOM from 'react-dom/client'
import { ConfigProvider } from './ConfigProvider.jsx'
import TokenCheckContainer from './auth/TokenCheckContainer.jsx'
import ApiStatusCheckContainer from './ApiStatusCheckContainer.jsx'

// import styles
import './assets/css/index.scss'

export default function App() {
    return (
        <ApiStatusCheckContainer>
            <TokenCheckContainer/>
        </ApiStatusCheckContainer>
    )
}

// render app to root element
ReactDOM.createRoot(document.getElementById('root')).render(
    <ConfigProvider><App/></ConfigProvider>
)
