# Private Chat

## ROADMAP
### Version 1.0
### Setup
- [X] Setup api-base and modify for private chat use    
    - [X] Clean unnecessary components
    - [X] User authentication
    - [X] Login and logout system
    - [X] JWT token blacklisting system
    - [X] User register command
    - [X] User password reset command
    - [X] Implement security middleware for (SSL check, XSS escape, CSRF, etc)
    - [X] Change user login main property to username auth (email is not required)
    - [X] Implement rate limiting for non-authenticated users
    - [X] Implement user profile image to user entity (avatar in base64)
- [X] Setup react-app-base and modify for private chat use
- [X] Modify react-app-base for private chat use
    - [X] Implement config system for frontend somthing like .env
    - [X] Implement api status check (check if api is running)
    - [X] Implement api status check for handle maintenance mode, security stop, etc
- [X] Implement user authentication to frontend
    - [X] Implement login system
    - [X] Implement logout system
- [X] Remove first name and last name from user entity
- [ ] Basic frontend styling
- [ ] Try docker images building + create tesing docker-compose for production use
- [ ] Base refactoring
### Chat system
- [ ] Global chat system
    - [ ] Create entity for global chat (message, sender(id), timestamp)
    - [ ] API for send chat message (POST METHOD)
    - [ ] API FOR GET CHAT MESSAGES (only last 50 messages) (GET METHOD)
        - [ ] Implement get more messages for load older messages (scroll message history)
    - [ ] For chat operations over API (send/get message) implement chat type=global (in furure will be implemented for type=private)
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
