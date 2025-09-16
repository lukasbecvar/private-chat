<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PrivateChatMessageRepository;

/**
 * Class PrivateChatMessage
 *
 * The PrivateChatMessage database table mapping entity
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'private_messages')]
#[ORM\Index(name: 'chat_id_idx', columns: ['chat_id'])]
#[ORM\Index(name: 'sender_idx', columns: ['sender_id'])]
#[ORM\Index(name: 'timestamp_idx', columns: ['timestamp'])]
#[ORM\Index(name: 'receiver_idx', columns: ['receiver_id'])]
#[ORM\Index(name: 'chat_timestamp_idx', columns: ['chat_id', 'timestamp'])]
#[ORM\Entity(repositoryClass: PrivateChatMessageRepository::class)]
class PrivateChatMessage
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $timestamp;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'receiver_id', referencedColumnName: 'id', nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(length: 64)]
    private ?string $chat_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(User $receiver): static
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getChatId(): ?string
    {
        return $this->chat_id;
    }

    public function setChatId(string $chat_id): static
    {
        $this->chat_id = $chat_id;
        return $this;
    }
}
