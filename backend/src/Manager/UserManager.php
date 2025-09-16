<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\User;
use App\Util\VisitorInfoUtil;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserManager
 *
 * Manager for manipulating with users database system
 *
 * @package App\Manager
 */
class UserManager
{
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private UserRepository $userRepository;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        LogManager $logManager,
        ErrorManager $errorManager,
        UserRepository $userRepository,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Get user repository
     *
     * @return UserRepository The user repository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * Check if user id exist in database
     *
     * @param int $id The id of the user
     *
     * @return bool True if user exists, false otherwise
     */
    public function checkIfUserIdExistInDatabase(int $id): bool
    {
        return $this->userRepository->find($id) !== null;
    }

    /**
     * Check if user username already registered in database
     *
     * @param string $username The username of the user
     *
     * @return bool True if user exists, false otherwise
     */
    public function checkIfUserIsAlreadyRegistered(string $username): bool
    {
        return $this->userRepository->findByUsername($username) !== null;
    }

    /**
     * Get user id by username
     *
     * @param string $username The username address of the user
     *
     * @return int The user id or null if user does not exist
     */
    public function getUserIdByUsername(string $username): int
    {
        // get user object
        $user = $this->userRepository->findByUsername($username);

        // get user id
        $id = $user !== null ? $user->getId() : null;

        // check if user id found
        if ($id === null) {
            $this->errorManager->handleError(
                message: 'Error retrieving user id by username: ' . $username,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $id;
    }

    /**
     * Get user username by id
     *
     * @param int $id The user id
     *
     * @return string The user username or null if user not found
     */
    public function getUserUsernameById(int $id): string
    {
        // get user object
        $user = $this->userRepository->find($id);

        // get user username
        $username = $user !== null ? $user->getUsername() : null;

        // check if user username found
        if ($username === null) {
            $this->errorManager->handleError(
                message: 'Error retrieving user username by id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $username;
    }

    /**
     * Get users list
     *
     * @return array<array<string, array<string>|int|string|null>> The users list
     */
    public function getUsersList(): array
    {
        $list = [];

        // get users
        $users = $this->userRepository->findAll();

        // add all users to list
        foreach ($users as $user) {
            $registerTime = $user->getRegisterTime();
            $lastLoginTime = $user->getLastLoginTime();

            // format time values
            if ($registerTime != null) {
                $registerTime = $registerTime->format('Y-m-d H:i:s');
            }
            if ($lastLoginTime != null) {
                $lastLoginTime = $lastLoginTime->format('Y-m-d H:i:s');
            }

            $list[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'register-time' => $registerTime ?? 'Never',
                'last-login-time' => $lastLoginTime ?? 'Never',
                'ip-address' => $user->getIpAddress(),
                'browser' => $this->visitorInfoUtil->getBrowserShortify($user->getUserAgent() ?? 'Unknown'),
                'status' => $user->getStatus()
            ];
        }

        return $list;
    }

    /**
     * Register new user to database
     *
     * @param string $username The username of the user
     * @param string $password The password of the user
     *
     * @return void
     */
    public function registerUser(string $username, string $password): void
    {
        // check if user is already registered
        if ($this->checkIfUserIsAlreadyRegistered($username)) {
            $this->errorManager->handleError(
                message: 'User: ' . $username . ' already exists',
                code: JsonResponse::HTTP_CONFLICT
            );
        }

        // get visitor info
        $ipAddress = $this->visitorInfoUtil->getIP();
        $userAgent = $this->visitorInfoUtil->getUserAgent();

        // check if user info is valid
        if ($ipAddress == null || $userAgent == null) {
            $this->errorManager->handleError(
                message: 'Invalid user info: ip address or user agent is null',
                code: JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // create user entity
        $user = new User();

        // hash password
        $passwordHash = $this->passwordHasher->hashPassword($user, $password);

        // set user properties
        $user->setUsername($username)
            ->setRoles(['ROLE_USER'])
            ->setPassword($passwordHash)
            ->setRegisterTime(new DateTime())
            ->setLastLoginTime(new DateTime())
            ->setIpAddress($ipAddress)
            ->setUserAgent($userAgent)
            ->setStatus('active')
            ->setProfilePic('nonset-default');

        // save user to database
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to register user',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // log action to database
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'New user registered: ' . $username,
            level: LogManager::LEVEL_NOTICE
        );
    }

    /**
     * Get user info
     *
     * @param int $id The user id
     *
     * @return array<string, array<int, string>|string|bool> The user info
     */
    public function getUserInfo(int $id): array
    {
        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // get user info
        $username = $user->getUsername();
        $roles = $user->getRoles();
        $registerTime = $user->getRegisterTime();
        $lastLoginTime = $user->getLastLoginTime();
        $ipAddress = $user->getIpAddress();
        $userAgent = $user->getUserAgent();
        $status = $user->getStatus();
        $profilePicture = $user->getProfilePic();

        // check if profile picture is set
        if ($profilePicture === null) {
            $profilePicture = 'nonset-default';
        }

        // check if user info found
        if (
            $username === null || $roles === null
            || $registerTime === null || $lastLoginTime === null || $ipAddress === null
            || $userAgent === null || $status === null
        ) {
            $this->errorManager->handleError(
                message: 'User id: ' . $id . ' info not found',
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // return user status
        return [
            'username' => $username,
            'roles' => $roles,
            'register-time' => $registerTime->format('Y-m-d H:i:s'),
            'last-login-time' => $lastLoginTime->format('Y-m-d H:i:s'),
            'ip-address' => $ipAddress,
            'user-agent' => $userAgent,
            'status' => $status,
            'is-active' => $status === 'active',
            'profile-picture' => $profilePicture
        ];
    }

    /**
     * Update user password
     *
     * @param int $id User id to password update
     * @param string $newPassword New password
     *
     * @return void
     */
    public function updateUserPassword(int $id, string $newPassword): void
    {
        // get user by id
        $user = $this->userRepository->find($id);

        // check if user found
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found!',
                code: JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        // hash password
        $newPassword = $this->passwordHasher->hashPassword($user, $newPassword);

        // update user password
        $user->setPassword($newPassword);

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error while updating user password!',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // get username by user id
        $username = $this->getUserUsernameById($id);

        // log event
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User: ' . $username . ' password changed',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Update last login time
     *
     * @param string $identifier The user identifier
     *
     * @return void
     */
    public function updateLastLoginTime(string $identifier): void
    {
        // get user by identifier
        $user = $this->userRepository->findOneBy(['username' => $identifier]);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with identifier: ' . $identifier,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // get visitor info
        $ipAddress = $this->visitorInfoUtil->getIP() ?? 'Unknown';
        $userAgent = $this->visitorInfoUtil->getUserAgent() ?? 'Unknown';

        // update user data
        $user->setLastLoginTime(new DateTime());
        $user->setIpAddress($ipAddress);
        $user->setUserAgent($userAgent);

        // save user to database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to flush user entity update',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }
    }

    /**
     * Delete user by id
     *
     * @param int $id The user id
     *
     * @return void
     */
    public function deleteUser(int $id): void
    {
        // get user
        $user = $this->userRepository->find($id);

        // get user username by id
        $username = $this->getUserUsernameById($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found: ' . $username,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // delete user
        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to delete user: ' . $username,
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // log action to database
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User deleted: ' . $username,
            level: LogManager::LEVEL_NOTICE
        );
    }

    /**
     * Update user status
     *
     * @param int $id The user id
     * @param string $status The new user status
     *
     * @return void
     */
    public function updateUserStatus(int $id, string $status): void
    {
        // get user by id
        $user = $this->userRepository->find($id);

        // check if user found
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User id: ' . $id . ' not found',
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // get old user status
        $oldStatus = $user->getStatus();

        // update user status
        $user->setStatus($status);

        // flush changes to database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'User id: ' . $id . ' could not be updated',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // get user username by id
        $username = $this->getUserUsernameById($id);

        // log action
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User: ' . $username . ' updated status to: ' . $status . ' old status was: ' . $oldStatus,
            level: LogManager::LEVEL_NOTICE
        );
    }

    /**
     * Get user status
     *
     * @param int $id The user id
     *
     * @return string The user status
     */
    public function getUserStatus(int $id): string
    {
        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User id: ' . $id . ' not found',
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // get user status
        $status = $user->getStatus();

        // check if user status found
        if ($status === null) {
            $this->errorManager->handleError(
                message: 'User id: ' . $id . ' status not found',
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $status;
    }

    /**
     * Reset user password
     *
     * @param int $id The user id
     *
     * @return string
     */
    public function resetUserPassword(int $id): string
    {
        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // generate new password
        $password = ByteString::fromRandom(16);

        // hash password
        $passwordHash = $this->passwordHasher->hashPassword($user, $password);

        // update user password
        $user->setPassword($passwordHash);

        // save user to database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to flush user entity update',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // get user username by id
        $username = $this->getUserUsernameById($id);

        // log action to database
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User password reset: ' . $username,
            level: LogManager::LEVEL_INFO
        );

        // return new password
        return $password;
    }

    /**
     * Check if user has specific role
     *
     * @param int $id The user id
     * @param string $role The role to check
     *
     * @return bool True if user has role, false otherwise
     */
    public function checkIfUserHasRole(int $id, string $role): bool
    {
        // validate role format
        $role = strtoupper($role);
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // check if user has role
        return in_array($role, $user->getRoles());
    }

    /**
     * Add role to specific user
     *
     * @param int $id The user id
     * @param string $role The role to add
     *
     * @return void
     */
    public function addRoleToUser(int $id, string $role): void
    {
        // validate role format
        $role = strtoupper($role);
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // check if user has role
        if ($this->checkIfUserHasRole($id, $role)) {
            $this->errorManager->handleError(
                message: 'User already has role: ' . $role,
                code: JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // add role to user
        $user->addRole($role);

        // save user to database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to flush user entity update',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // get user username by id
        $username = $this->getUserUsernameById($id);

        // log action to database
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User role added: ' . $username . ' - ' . $role,
            level: LogManager::LEVEL_WARNING
        );
    }

    /**
     * Remove role from specific user
     *
     * @param int $id The id of the user
     * @param string $role The role to remove
     *
     * @return void
     */
    public function removeRoleFromUser(int $id, string $role): void
    {
        // validate role format
        $role = strtoupper($role);
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . $role;
        }

        // get user
        $user = $this->userRepository->find($id);

        // check if user exists
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'User not found with id: ' . $id,
                code: JsonResponse::HTTP_NOT_FOUND
            );
        }

        // check if user has role
        if (!$this->checkIfUserHasRole($id, $role)) {
            $this->errorManager->handleError(
                message: 'User does not have role: ' . $role,
                code: JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // remove role from user
        $user->removeRole($role);

        // save user to database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to flush user entity update',
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                exceptionMessage: $e->getMessage()
            );
        }

        // get user username by id
        $username = $this->getUserUsernameById($id);

        // log action to database
        $this->logManager->saveLog(
            name: 'user-manager',
            message: 'User role removed: ' . $username . ' - ' . $role,
            level: LogManager::LEVEL_WARNING
        );
    }
}
