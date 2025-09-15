# Private Chat

## ROADMAP
- [ ] Global chat system backend
    - [ ] Create entity for global chat (message, sender(id), timestamp)
    - [ ] API for send chat message (POST METHOD)
    - [ ] API FOR GET CHAT MESSAGES (only last 50 messages) (GET METHOD)
        - [ ] Implement get more messages for load older messages (scroll message history)
    - [ ] For chat operations over API (send/get message) implement chat type=global (in furure will be implemented for type=private)
- [ ] Frontend
    - [ ] Chat select system (hard coded for global chat for now)
    - [ ] Chat system (frontend)
        - [ ] Form for send message
        - [ ] Implement chat polling system (for get new messages)
        - [ ] Implement chat history scroll system
        - [ ] Implement chat type for global chat (in furure will be implemented for type=private)
- [ ] Refactor version 1.0
- [ ] Create simple documentation in README
- [ ] Plan for version 2.0
- [ ] Publish version 1.0 to github public repository

## License
This software is licensed under the [MIT license](LICENSE). 
