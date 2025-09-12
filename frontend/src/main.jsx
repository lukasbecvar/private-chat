import ReactDOM from 'react-dom/client'
import { ConfigProvider } from './ConfigProvider.jsx'
import ApiStatusCheck from './components/ApiStatusCheck.jsx'

// import styles
import './assets/css/index.scss'

export default function App() {
    return (
        <ApiStatusCheck>
            <div className="app">
                <h1>Private Chat</h1>
                <p>API is running</p>
            </div>
        </ApiStatusCheck>
    )
}

// render app to root element
ReactDOM.createRoot(document.getElementById('root')).render(
    <ConfigProvider><App/></ConfigProvider>
)
