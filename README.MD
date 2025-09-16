# Private Chat

## ROADMAP
- [ ] Backend
    - [X] PrivateChatMessage entity (message, timestamp, sender_id, receiver_id, chat_id(calculated from user-ids) readed_status)
    - [X] PrivateChatMessage data fixture
    - [X] Deterministic and symmetric chat ID generation method
    - [ ] API endpoint for get user list (for building chat list)
        - [ ] id, username, roles, profile picture
        - [ ] get chat_id from curren logged in user and receiver_id from user list
    - [ ] API endpoint for get chat messages by chat_id
        - [ ] Prevent get messages from other users chats
        - [ ] Option to get messages after timestamp
    - [ ] DTO validation for for message send
    - [ ] API endpoint for send message to chat
- [ ] Frontend
    - [ ] Chat list component
    - [ ] Chat box component
    - [ ] Send chat message component
- [ ] System for infinite scroll chat history (scroll to top like classic messenger)
- [ ] Chat list search (search by username)
- [ ] Show last chat message in chat list under username
- [ ] Chat messages encryption with AES

## Version 2.0
- [ ] API version check (check if backend version is compatible with frontend version)
- [ ] Ban system
    - [ ] Command for ban/unban user
    - [ ] Show banned status in user list and chat list

## Version 3.0
- [ ] Global chat for all users

## License
This software is licensed under the [MIT license](LICENSE). 
