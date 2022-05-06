<?php

namespace App\Entity;

use App\Repository\NotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationsRepository::class)
 */
class Notifications
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="notifications")
     */
    private $clinic;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notification;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isRead;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityTracker::class, inversedBy="notifications", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="availability_tracker_id", referencedColumnName="id", nullable=true)
     */
    private $availabilityTracker;

    /**
     * @ORM\ManyToOne(targetEntity=Orders::class, inversedBy="notifications")
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity=Distributors::class, inversedBy="notifications")
     */
    private $distributor;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $isTracking;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $isOrder;

    /**
     * @ORM\Column(type="integer")
     */
    private $isMessage;

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

    public function getClinic(): ?Clinics
    {
        return $this->clinic;
    }

    public function setClinic(?Clinics $clinic): self
    {
        $this->clinic = $clinic;

        return $this;
    }

    public function getNotification(): ?string
    {
        return $this->notification;
    }

    public function setNotification(string $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

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

    public function getAvailabilityTracker(): ?AvailabilityTracker
    {
        return $this->availabilityTracker;
    }

    public function setAvailabilityTracker(?AvailabilityTracker $availabilityTracker): self
    {
        $this->availabilityTracker = $availabilityTracker;

        return $this;
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

    public function getIsTracking(): ?int
    {
        return $this->isTracking;
    }

    public function setIsTracking(?int $isTracking): self
    {
        $this->isTracking = $isTracking;

        return $this;
    }

    public function getIsOrder(): ?int
    {
        return $this->isOrder;
    }

    public function setIsOrder(?int $isOrder): self
    {
        $this->isOrder = $isOrder;

        return $this;
    }

    public function getIsMessage(): ?int
    {
        return $this->isMessage;
    }

    public function setIsMessage(int $isMessage): self
    {
        $this->isMessage = $isMessage;

        return $this;
    }
}
