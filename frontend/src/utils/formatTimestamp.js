export const formatTimestamp = (timestamp) => {
    const messageDate = new Date(timestamp);
    const now = new Date();

    const isToday = messageDate.getDate() === now.getDate() &&
                    messageDate.getMonth() === now.getMonth() &&
                    messageDate.getFullYear() === now.getFullYear();

    const isYesterday = messageDate.getDate() === now.getDate() - 1 &&
                        messageDate.getMonth() === now.getMonth() &&
                        messageDate.getFullYear() === now.getFullYear();

    const timeFormat = { hour: '2-digit', minute: '2-digit', hour12: false };

    if (isToday) {
        return messageDate.toLocaleTimeString([], timeFormat);
    }

    if (isYesterday) {
        return `Yesterday at ${messageDate.toLocaleTimeString([], timeFormat)}`;
    }

    return messageDate.toLocaleDateString([], { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + messageDate.toLocaleTimeString([], timeFormat);
};