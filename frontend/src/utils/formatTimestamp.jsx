export function formatTimestamp(timestamp) {
    // convert timestamp to date
    const messageDate = new Date(timestamp)

    // get current date
    const now = new Date()

    // check if the timestamp is from today
    const isToday = messageDate.getDate() === now.getDate() &&
                    messageDate.getMonth() === now.getMonth() &&
                    messageDate.getFullYear() === now.getFullYear()

    // check if the timestamp is from yesterday
    const isYesterday = messageDate.getDate() === now.getDate() - 1 &&
                        messageDate.getMonth() === now.getMonth() &&
                        messageDate.getFullYear() === now.getFullYear()

    // define time format (24h, two digits for hour/minute)
    const timeFormat = { hour: '2-digit', minute: '2-digit', hour12: false }

    // return today's time
    if (isToday) {
        return messageDate.toLocaleTimeString([], timeFormat)
    }

    // return yesterday's time
    if (isYesterday) {
        return `Yesterday at ${messageDate.toLocaleTimeString([], timeFormat)}`
    }

    // return formatted date and time
    return messageDate.toLocaleDateString([], { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + messageDate.toLocaleTimeString([], timeFormat)
}
