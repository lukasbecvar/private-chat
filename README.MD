# Private Chat

# ROADMAP
## Version 1.0
- [X] Backend
    - [X] PrivateChatMessage entity (message, timestamp, sender_id, receiver_id, chat_id(calculated from user-ids) readed_status)
    - [X] PrivateChatMessage data fixture
    - [X] Deterministic and symmetric chat ID generation method
    - [X] API endpoint for get user list (for building chat list)
        - [X] id, username, roles, profile picture
        - [X] get chat_id from curren logged in user and receiver_id from user list
    - [X] API endpoint for get chat messages by chat_id
        - [X] Prevent get messages from other users chats
        - [X] Option to get messages with limit and offset parameters
    - [X] DTO validation for for message send
    - [X] API endpoint for send message to chat
        - [X] Prevent send messages to other users chats
- [ ] Frontend
    - [X] Chat list component
    - [X] Chat box component
    - [X] Send chat message component
    - [ ] Chat history refresh show new messages wihout reloading page
    - [ ] System for infinite scroll chat history (scroll to top like classic messenger)
        - [ ] Scroll from bottom to top (like in classic messenger)
- [ ] Show last chat message in chat list under username
- [ ] Sort chat list by last message time
- [ ] Chat list search (search by username)
- [ ] Highlight admin usernames in chat list
- [ ] Simple readme documentation

## Version 2.0
- [ ] User online/offline status (show online status in chat list)
- [ ] Show send time in chat history 
- [ ] API version check (check if backend version is compatible with frontend version)
- [ ] Ban system
    - [ ] Command for ban/unban user
    - [ ] Show banned status in user list and chat list

## Version 3.0
- [ ] Global chat for all users

## Version 4.0
- [ ] Chat messages encryption with AES

## Version 5.0
- [ ] User info view
    - [ ] Show user info in chat component on right side after clicking on ? button
        - [ ] User info
            - [ ] Profile picture
            - [ ] Username
            - [ ] Role
            - [ ] Registered time
            - [ ] Last login time

## Version 6.0
- [ ] File sharing
    - [ ] Image viewer
    - [ ] Video player
    - [ ] Audo player
    - [ ] Shared file downloading

## Version 7.0
- [ ] Admin system

## License
This software is licensed under the [MIT license](LICENSE). 
