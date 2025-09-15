# Private Chat

## ROADMAP
- [ ] Backend
    - [ ] API endpoint for get user list (for building chat list)
        - [ ] id, username, roles, profile picture
        - [ ] get chat_id from curren logged in user and receiver_id from user list
    - [ ] PrivateChatMessage entity (message, timestamp, sender_id, receiver_id, chat_id(calculated from user-ids) readed_status)
    - [ ] API endpoint for get chat messages by chat_id
        - [ ] Prevent get messages from other users chats
        - [ ] Option to get messages after timestamp
    - [ ] API endpoint for send message to chat
- [ ] Frontend
    - [ ] Chat list component
    - [ ] Chat box component
    - [ ] Send chat message component
- [ ] System for infinite scroll chat history (scroll to top like classic messenger)
- [ ] Chat list search (search by username)
- [ ] Show last chat message in chat list under username
- [ ] Chat messages encryption with AES

## License
This software is licensed under the [MIT license](LICENSE). 
