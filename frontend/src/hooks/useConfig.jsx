import { useContext } from 'react'
import { ConfigContext } from '../ConfigProvider'

// export hook for using config in components
export const useConfig = () => useContext(ConfigContext)
