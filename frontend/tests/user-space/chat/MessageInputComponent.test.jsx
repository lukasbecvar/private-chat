import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'

// import new message input component
import MessageInputComponent from '../../../src/user-space/chat/MessageInputComponent'

vi.mock('../../../src/hooks/useConfig', () => ({
    useConfig: () => ({
        getConfigValue: (key) => {
            const config = {
                API_URL: 'http://mock-api',
                API_TOKEN: 'mock-token',
                DEV_MODE: false
            }
            return config[key]
        }
    })
}))

global.fetch = vi.fn();

const mockChat = {
    'user-id': 'user-456'
}

describe('MessageInputComponent', () => {
    beforeEach(() => {
        fetch.mockClear();
    });

    // test sends message when user clicks send button
    it('sends a message when user types and clicks send', async () => {
        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ status: 'success' })
        })

        const onMessageSent = vi.fn()
        const setMessages = vi.fn()

        render(<MessageInputComponent chat={mockChat} onMessageSent={onMessageSent} setMessages={setMessages}/>)

        const input = screen.getByPlaceholderText('Type a message...')
        const sendButton = screen.getByTestId('send-button')

        fireEvent.change(input, { target: { value: 'Test message' } })
        fireEvent.click(sendButton)

        // expect send button to be disabled
        expect(sendButton).toBeDisabled()

        // expect api call
        await waitFor(() => {
            expect(fetch).toHaveBeenCalledWith(
                'http://mock-api/api/chat/send',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify({
                        message: 'Test message',
                        receiver_id: 'user-456'
                    })
                })
            )
        })

        // expect onMessageSent to be called
        await waitFor(() => {
            expect(onMessageSent).toHaveBeenCalled()
        })

        // expect input clear
        expect(input.value).toBe('')
    })

    // test displays an error message if sending a message fails
    it('displays an error indication if sending a message fails', async () => {
        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({ status: 'error', message: 'Send failed' })
        })

        const onMessageSent = vi.fn()
        const setMessages = vi.fn()

        render(<MessageInputComponent chat={mockChat} onMessageSent={onMessageSent} setMessages={setMessages}/>)

        const input = screen.getByPlaceholderText('Type a message...');
        const sendButton = screen.getByTestId('send-button');

        fireEvent.change(input, { target: { value: 'Failed message' } })
        fireEvent.click(sendButton)

        // expect send button to be disabled
        await waitFor(() => {
            expect(sendButton).not.toBeDisabled()
        })

        // expect input clear
        expect(input.value).toBe('');
        
        // expect set error message
        expect(setMessages).toHaveBeenCalledWith(expect.any(Function));
    })
})
