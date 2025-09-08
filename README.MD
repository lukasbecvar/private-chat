# Private Chat

# ROADMAP
## Version 1.0
- [ ] Setup api-base and modify for private chat use    
    - [ ] Clean unnecessary components
    - [ ] User authentication
    - [ ] Login and logout system
    - [ ] JWT token blacklisting system
    - [ ] JWT refresh token system (?? maybe)
    - [ ] User register command
    - [ ] User password reset command
    - [ ] Delete controller for user registration (allow registration only for admins in CLI for now)
    - [ ] Implement rate limiting for non-authenticated users
    - [ ] Implement security middleware for (SSL check, XSS escape, CSRF, etc)
    - [ ] Implement user profile image to user entity (avatar in base64)
- [ ] Global chat system
    - [ ] Create entity for global chat (message, sender(id), timestamp)
    - [ ] API for send chat message (POST METHOD)
    - [ ] API FOR GET CHAT MESSAGES (only last 50 messages) (GET METHOD)
        - [ ] Implement get more messages for load older messages (scroll message history)
    - [ ] For chat operations over API (send/get message) implement chat type=global (in furure will be implemented for type=private)
- [ ] Setup react-app-base and modify for private chat use
    - [ ] Implement system for configuration api url (for electron app)
        - [ ] file config for app (if api-url is not set, use api url config component and save to local storage)
- [ ] Implement user authentication to frontend
    - [ ] Implement login system
    - [ ] Implement logout system
- [ ] Chat select system (hard coded for global chat for now)
- [ ] Chat system
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
