<?php

namespace App\Entity;

use Deprecated;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Class User
 *
 * The User database table mapping entity
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'users_username_idx', columns: ['username'])]
#[ORM\Index(name: 'users_status_idx', columns: ['status'])]
#[ORM\Index(name: 'users_ip_address_idx', columns: ['ip_address'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $username;

    /** @var array<string> The user roles (json) */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $register_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $last_login_time = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $profile_pic = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return array<string> The user roles
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<string> $roles The user roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRegisterTime(): ?DateTimeInterface
    {
        return $this->register_time;
    }

    public function setRegisterTime(DateTimeInterface $register_time): static
    {
        $this->register_time = $register_time;

        return $this;
    }

    public function getLastLoginTime(): ?DateTimeInterface
    {
        return $this->last_login_time;
    }

    public function setLastLoginTime(DateTimeInterface $last_login_time): static
    {
        $this->last_login_time = $last_login_time;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(string $user_agent): static
    {
        // prevent maximal user agent length
        if (strlen($user_agent) > 255) {
            $user_agent = substr($user_agent, 0, 250) . "...";
        }

        $this->user_agent = $user_agent;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get identifier that represents this user
     *
     * @return string The username identifier
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    #[Deprecated(message: 'eraseCredentials() is deprecated since Symfony 7.3 and maybe will be removed in future')]
    public function eraseCredentials(): void
    {
    }

    /**
     * Add a role to the user if not already present
     *
     * @param string $role The role to add
     */
    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;

            // Reset array keys
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * Remove a role from the user if present
     *
     * @param string $role The role to remove
     */
    public function removeRole(string $role): static
    {
        $this->roles = array_values(
            array_filter(
                $this->roles,
                fn($r) => $r !== $role
            )
        );

        return $this;
    }

    public function getProfilePic(): ?string
    {
        return $this->profile_pic;
    }

    public function setProfilePic(string $profile_pic): static
    {
        $this->profile_pic = $profile_pic;

        return $this;
    }
}
