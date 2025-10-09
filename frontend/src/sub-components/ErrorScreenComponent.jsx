import { faRedo } from "@fortawesome/free-solid-svg-icons"
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome"

export default function ErrorScreenComponent({ errorMessage }) {
    
    // function to reload app
    function handleReload() {
        window.location.reload()
    }

    return (
        <center className="flex flex-col items-center justify-center h-screen text-4xl">
            {/* error message */}
            <div className="text-white">{errorMessage}</div>
            
            {/* app reload button */}
            <button
                className="
                    relative mt-8 inline-flex items-center px-6 py-3
                    text-lg font-medium rounded-md text-white
                    bg-gradient-to-r from-blue-500 to-blue-700 overflow-hidden
                    before:content-[''] before:absolute before:top-0 before:-left-full before:w-full before:h-full
                    before:bg-gradient-to-r before:from-transparent before:via-white before:to-transparent
                    before:opacity-30 before:skew-x-[-25deg]
                    hover:before:left-full hover:before:transition-left hover:before:duration-700
                "
                onClick={handleReload}
            >
                <FontAwesomeIcon icon={faRedo} className="w-6 h-6 mr-3"/>
                <span className="relative">Reload</span>
            </button>
        </center>
    )
}
