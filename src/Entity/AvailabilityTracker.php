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

    /**
     * @ORM\ManyToOne(targetEntity=Distributors::class, inversedBy="availabilityTrackers")
     */
    private $distributor;

    /**
     * @ORM\ManyToOne(targetEntity=ClinicCommunicationMethods::class, inversedBy="availabilityTrackers")
     */
    private $communication;

    /**
     * @ORM\OneToOne(targetEntity=Notifications::class, mappedBy="availabilityTracker", cascade={"persist", "remove"})
     */
    private $notifications;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
    }

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

    public function getDistributor(): ?Distributors
    {
        return $this->distributor;
    }

    public function setDistributor(?Distributors $distributor): self
    {
        $this->distributor = $distributor;

        return $this;
    }

    public function getCommunication(): ?ClinicCommunicationMethods
    {
        return $this->communication;
    }

    public function setCommunication(?ClinicCommunicationMethods $communication): self
    {
        $this->communication = $communication;

        return $this;
    }

    public function getNotifications(): ?Notifications
    {
        return $this->notifications;
    }

    public function setNotifications(?Notifications $notifications): self
    {
        // unset the owning side of the relation if necessary
        if ($notifications === null && $this->notifications !== null) {
            $this->notifications->setAvailabilityTracker(null);
        }

        // set the owning side of the relation if necessary
        if ($notifications !== null && $notifications->getAvailabilityTracker() !== $this) {
            $notifications->setAvailabilityTracker($this);
        }

        $this->notifications = $notifications;

        return $this;
    }
}
