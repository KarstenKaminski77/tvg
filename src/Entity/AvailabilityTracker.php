<?php

namespace App\Entity;

use App\Repository\AvailabilityTrackerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AvailabilityTrackerRepository::class)
 */
class AvailabilityTracker
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="availabilityTrackers")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="availabilityTrackers")
     */
    private $clinic;

    /**
     * @ORM\ManyToOne(targetEntity=CommunicationMethods::class, inversedBy="availabilityTrackers")
     */
    private $communication;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSent;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

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

    public function getCommunication(): ?CommunicationMethods
    {
        return $this->communication;
    }

    public function setCommunication(?CommunicationMethods $communication): self
    {
        $this->communication = $communication;

        return $this;
    }

    public function getIsSent(): ?bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): self
    {
        $this->isSent = $isSent;

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
