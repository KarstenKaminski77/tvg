<?php

namespace App\Entity;

use App\Repository\ChatParticipantsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChatParticipantsRepository::class)
 */
class ChatParticipants
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Orders::class, inversedBy="distributor")
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity=Distributors::class, inversedBy="chatParticipants")
     */
    private $distributor;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="chatParticipants")
     */
    private $clinic;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $distributorIsTyping;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ClinicIsTyping;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    public function __construct()
    {
        $this->setModified(new \DateTime());
        $this->setModified(new \DateTime());
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime());
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?Orders
    {
        return $this->orders;
    }

    public function setOrders(?Orders $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getDistributor(): ?Distributors
    {
        return $this->distributor;
    }

    public function setDistributor(?Distributors $distributor): self
    {
        $this->distributor = $distributor;

        return $this;
    }

    public function getClinic(): ?Clinics
    {
        return $this->clinic;
    }

    public function setClinic(?Clinics $clinic): self
    {
        $this->clinic = $clinic;

        return $this;
    }

    public function getDistributorIsTyping(): ?int
    {
        return $this->distributorIsTyping;
    }

    public function setDistributorIsTyping(int $distributorIsTyping): self
    {
        $this->distributorIsTyping = $distributorIsTyping;

        return $this;
    }

    public function getClinicIsTyping(): ?int
    {
        return $this->ClinicIsTyping;
    }

    public function setClinicIsTyping(int $ClinicIsTyping): self
    {
        $this->ClinicIsTyping = $ClinicIsTyping;

        return $this;
    }

    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }
}
