export default function ErrorScreen({ errorMessage }) {
    const handleReload = () => {
        window.location.reload();
    };

    return (
        <center className="flex flex-col items-center justify-center h-screen text-4xl mt-[-80px] p-4 text-white">
            <div>{errorMessage}</div>
            <button
                className="relative mt-8 inline-flex items-center px-6 py-3 border border-transparent text-lg font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-blue-500 to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 overflow-hidden
                           before:content-[''] before:absolute before:top-0 before:-left-full before:w-full before:h-full before:bg-gradient-to-r before:from-transparent before:via-white before:to-transparent before:opacity-30 before:transform before:skew-x-[-25deg] hover:before:left-full hover:before:transition-all hover:before:duration-700"
                onClick={handleReload}
            >
                <svg className="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.0001 10.4311C20.0001 15.6422 15.6422 20.0001 10.4311 20.0001C5.21999 20.0001 0.862061 15.6422 0.862061 10.4311C0.862061 5.21999 5.21999 0.862061 10.4311 0.862061C13.5362 0.862061 16.2958 2.37983 18.0001 4.76206" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M18.0001 1V4.76206H14.238" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
                <span className="relative">Reload</span>
            </button>
        </center>
    );
}
