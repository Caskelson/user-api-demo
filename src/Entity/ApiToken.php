<?php

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ApiTokenRepository::class)
 */
class ApiToken
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"token"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"token"})
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"token"})
     */
    private $expiresAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="apiTokens")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"token_details"})
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = bin2hex(random_bytes(32));
        $this->expiresAt = new \DateTime('+1 hour');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
