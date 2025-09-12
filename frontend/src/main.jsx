import ReactDOM from 'react-dom/client'
import { ConfigProvider } from './ConfigProvider.jsx'
import JWTCheck from './components/JWTCheck.jsx'
import ApiStatusCheck from './components/ApiStatusCheck.jsx'

// import styles
import './assets/css/index.scss'

export default function App() {
    return (
        <ApiStatusCheck>
            <JWTCheck/>
        </ApiStatusCheck>
    )
}

// render app to root element
ReactDOM.createRoot(document.getElementById('root')).render(
    <ConfigProvider><App/></ConfigProvider>
)
