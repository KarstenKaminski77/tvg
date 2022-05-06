<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrdersRepository::class)
 */
class Orders
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="orders")
     */
    private $clinic;

    /**
     * @ORM\ManyToOne(targetEntity=Addresses::class, inversedBy="orders")
     */
    private $address;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $deliveryFee;

    /**
     * @ORM\Column(type="float")
     */
    private $subTotal;

    /**
     * @ORM\Column(type="float")
     */
    private $tax;

    /**
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * @ORM\Column (type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=EventLog::class, mappedBy="orders")
     */
    private $eventLogs;

    /**
     * @ORM\OneToMany(targetEntity=OrderItems::class, mappedBy="orders")
     */
    private $orderItems;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\OneToOne(targetEntity=Baskets::class, inversedBy="orders", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $basket;

    /**
     * @ORM\ManyToOne(targetEntity=Addresses::class, inversedBy="xxxx")
     */
    private $billingAddress;

    /**
     * @ORM\OneToMany(targetEntity=ChatParticipants::class, mappedBy="orders")
     */
    private $distributor;

    /**
     * @ORM\OneToMany(targetEntity=ChatMessages::class, mappedBy="orders")
     */
    private $chatMessages;

    /**
     * @ORM\OneToMany(targetEntity=Notifications::class, mappedBy="orders")
     */
    private $notifications;

    /**
     * @ORM\OneToMany(targetEntity=OrderStatus::class, mappedBy="orders")
     */
    private $orderStatuses;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }

        $this->eventLogs = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
        $this->distributor = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->orderStatuses = new ArrayCollection();
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

    public function getAddress(): ?Addresses
    {
        return $this->address;
    }

    public function setAddress(?Addresses $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getDeliveryFee(): ?float
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?float $deliveryFee): self
    {
        $this->deliveryFee = $deliveryFee;

        return $this;
    }

    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    public function setSubTotal(float $subTotal): self
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    public function getTax(): ?float
    {
        return $this->tax;
    }

    public function setTax(float $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    /**
     * @return Collection|EventLog[]
     */
    public function getEventLogs(): Collection
    {
        return $this->eventLogs;
    }

    public function addEventLog(EventLog $eventLog): self
    {
        if (!$this->eventLogs->contains($eventLog)) {
            $this->eventLogs[] = $eventLog;
            $eventLog->setOrders($this);
        }

        return $this;
    }

    public function removeEventLog(EventLog $eventLog): self
    {
        if ($this->eventLogs->removeElement($eventLog)) {
            // set the owning side to null (unless already changed)
            if ($eventLog->getOrders() === $this) {
                $eventLog->setOrders(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderItems[]
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItems $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setOrders($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItems $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrders() === $this) {
                $orderItem->setOrders(null);
            }
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getBasket(): ?Baskets
    {
        return $this->basket;
    }

    public function setBasket(Baskets $basket): self
    {
        $this->basket = $basket;

        return $this;
    }

    public function getBillingAddress(): ?Addresses
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Addresses $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return Collection<int, ChatParticipants>
     */
    public function getDistributor(): Collection
    {
        return $this->distributor;
    }

    public function addDistributor(ChatParticipants $distributor): self
    {
        if (!$this->distributor->contains($distributor)) {
            $this->distributor[] = $distributor;
            $distributor->setOrders($this);
        }

        return $this;
    }

    public function removeDistributor(ChatParticipants $distributor): self
    {
        if ($this->distributor->removeElement($distributor)) {
            // set the owning side to null (unless already changed)
            if ($distributor->getOrders() === $this) {
                $distributor->setOrders(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChatMessages>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ChatMessages $chatMessage): self
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages[] = $chatMessage;
            $chatMessage->setOrders($this);
        }

        return $this;
    }

    public function removeChatMessage(ChatMessages $chatMessage): self
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getOrders() === $this) {
                $chatMessage->setOrders(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notifications>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notifications $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setOrdersId($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getOrdersId() === $this) {
                $notification->setOrdersId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderStatus>
     */
    public function getOrderStatuses(): Collection
    {
        return $this->orderStatuses;
    }

    public function addOrderStatus(OrderStatus $orderStatus): self
    {
        if (!$this->orderStatuses->contains($orderStatus)) {
            $this->orderStatuses[] = $orderStatus;
            $orderStatus->setOrders($this);
        }

        return $this;
    }

    public function removeOrderStatus(OrderStatus $orderStatus): self
    {
        if ($this->orderStatuses->removeElement($orderStatus)) {
            // set the owning side to null (unless already changed)
            if ($orderStatus->getOrders() === $this) {
                $orderStatus->setOrders(null);
            }
        }

        return $this;
    }
}
