<?php

namespace App\Entity;

use App\Repository\DistributorsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DistributorsRepository::class)
 */
class Distributors
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $distributorName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $about;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $operatingHours;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $refundPolicy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $salesTaxPolicy;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isManufaturer;

    /**
     * @ORM\Column(type="integer", nullable=true, nullable=true)
     */
    private $themeId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=Baskets::class, mappedBy="distributor")
     */
    private $baskets;

    /**
     * @ORM\OneToMany(targetEntity=DistributorClinicPrices::class, mappedBy="distributor")
     */
    private $distributorClinicPrices;

    /**
     * @ORM\OneToMany(targetEntity=DistributorProducts::class, mappedBy="distributor")
     */
    private $distributorProducts;

    /**
     * @ORM\OneToMany(targetEntity=DistributorUsers::class, mappedBy="distributor")
     */
    private $distributorUsers;

    /**
     * @ORM\OneToMany(targetEntity=EventLog::class, mappedBy="distributor")
     */
    private $eventLogs;

    /**
     * @ORM\OneToMany(targetEntity=OrderItems::class, mappedBy="distributor")
     */
    private $orderItems;

    /**
     * @ORM\OneToMany(targetEntity=ListItems::class, mappedBy="distributor")
     */
    private $listItems;

    /**
     * @ORM\OneToMany(targetEntity=BasketItems::class, mappedBy="distributor")
     */
    private $basketItems;

    /**
     * @ORM\OneToMany(targetEntity=ClinicProducts::class, mappedBy="distributor")
     */
    private $clinicProducts;

    /**
     * @ORM\OneToMany(targetEntity=AvailabilityTracker::class, mappedBy="distributor")
     */
    private $availabilityTrackers;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $poNumberPrefix;

    /**
     * @ORM\OneToMany(targetEntity=ChatParticipants::class, mappedBy="distributor")
     */
    private $chatParticipants;

    /**
     * @ORM\OneToMany(targetEntity=ChatMessages::class, mappedBy="distributor")
     */
    private $chatMessages;

    /**
     * @ORM\OneToMany(targetEntity=Notifications::class, mappedBy="distributor")
     */
    private $notifications;

    /**
     * @ORM\OneToMany(targetEntity=OrderStatus::class, mappedBy="distributor")
     */
    private $orderStatuses;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }

        $this->distributors = new ArrayCollection();
        $this->baskets = new ArrayCollection();
        $this->distributorClinicPrices = new ArrayCollection();
        $this->distributorProducts = new ArrayCollection();
        $this->distributorUsers = new ArrayCollection();
        $this->eventLogs = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
        $this->listItems = new ArrayCollection();
        $this->basketItems = new ArrayCollection();
        $this->clinicProducts = new ArrayCollection();
        $this->availabilityTrackers = new ArrayCollection();
        $this->chatParticipants = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->orderStatuses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDistributorName(): ?string
    {
        return $this->distributorName;
    }

    public function setDistributorName(string $distributorName): self
    {
        $this->distributorName = $distributorName;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(string $about): self
    {
        $this->about = $about;

        return $this;
    }

    public function getOperatingHours(): ?string
    {
        return $this->operatingHours;
    }

    public function setOperatingHours(string $operatingHours): self
    {
        $this->operatingHours = $operatingHours;

        return $this;
    }

    public function getRefundPolicy(): ?string
    {
        return $this->refundPolicy;
    }

    public function setRefundPolicy(string $refundPolicy): self
    {
        $this->refundPolicy = $refundPolicy;

        return $this;
    }

    public function getSalesTaxPolicy(): ?string
    {
        return $this->salesTaxPolicy;
    }

    public function setSalesTaxPolicy(string $salesTaxPolicy): self
    {
        $this->salesTaxPolicy = $salesTaxPolicy;

        return $this;
    }

    public function getIsManufaturer(): ?bool
    {
        return $this->isManufaturer;
    }

    public function setIsManufaturer(bool $isManufaturer): self
    {
        $this->isManufaturer = $isManufaturer;

        return $this;
    }

    public function getThemeId(): ?int
    {
        return $this->themeId;
    }

    public function setThemeId(?int $themeId): self
    {
        $this->themeId = $themeId;

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
     * @return Collection|Baskets[]
     */
    public function getBaskets(): Collection
    {
        return $this->baskets;
    }

    public function addBasket(Baskets $basket): self
    {
        if (!$this->baskets->contains($basket)) {
            $this->baskets[] = $basket;
            $basket->setDistributor($this);
        }

        return $this;
    }

    public function removeBasket(Baskets $basket): self
    {
        if ($this->baskets->removeElement($basket)) {
            // set the owning side to null (unless already changed)
            if ($basket->getDistributor() === $this) {
                $basket->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DistributorClinicPrices[]
     */
    public function getDistributorClinicPrices(): Collection
    {
        return $this->distributorClinicPrices;
    }

    public function addDistributorClinicPrice(DistributorClinicPrices $distributorClinicPrice): self
    {
        if (!$this->distributorClinicPrices->contains($distributorClinicPrice)) {
            $this->distributorClinicPrices[] = $distributorClinicPrice;
            $distributorClinicPrice->setDistributor($this);
        }

        return $this;
    }

    public function removeDistributorClinicPrice(DistributorClinicPrices $distributorClinicPrice): self
    {
        if ($this->distributorClinicPrices->removeElement($distributorClinicPrice)) {
            // set the owning side to null (unless already changed)
            if ($distributorClinicPrice->getDistributor() === $this) {
                $distributorClinicPrice->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DistributorProducts[]
     */
    public function getDistributorProducts(): Collection
    {
        return $this->distributorProducts;
    }

    public function addDistributorProduct(DistributorProducts $distributorProduct): self
    {
        if (!$this->distributorProducts->contains($distributorProduct)) {
            $this->distributorProducts[] = $distributorProduct;
            $distributorProduct->setDistributor($this);
        }

        return $this;
    }

    public function removeDistributorProduct(DistributorProducts $distributorProduct): self
    {
        if ($this->distributorProducts->removeElement($distributorProduct)) {
            // set the owning side to null (unless already changed)
            if ($distributorProduct->getDistributor() === $this) {
                $distributorProduct->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DistributorUsers[]
     */
    public function getDistributorUsers(): Collection
    {
        return $this->distributorUsers;
    }

    public function addDistributorUser(DistributorUsers $distributorUser): self
    {
        if (!$this->distributorUsers->contains($distributorUser)) {
            $this->distributorUsers[] = $distributorUser;
            $distributorUser->setDistributor($this);
        }

        return $this;
    }

    public function removeDistributorUser(DistributorUsers $distributorUser): self
    {
        if ($this->distributorUsers->removeElement($distributorUser)) {
            // set the owning side to null (unless already changed)
            if ($distributorUser->getDistributor() === $this) {
                $distributorUser->setDistributor(null);
            }
        }

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
            $eventLog->setDistributor($this);
        }

        return $this;
    }

    public function removeEventLog(EventLog $eventLog): self
    {
        if ($this->eventLogs->removeElement($eventLog)) {
            // set the owning side to null (unless already changed)
            if ($eventLog->getDistributor() === $this) {
                $eventLog->setDistributor(null);
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
            $orderItem->setDistributor($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItems $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getDistributor() === $this) {
                $orderItem->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ListItems>
     */
    public function getListItems(): Collection
    {
        return $this->listItems;
    }

    public function addListItem(ListItems $listItem): self
    {
        if (!$this->listItems->contains($listItem)) {
            $this->listItems[] = $listItem;
            $listItem->setDistributor($this);
        }

        return $this;
    }

    public function removeListItem(ListItems $listItem): self
    {
        if ($this->listItems->removeElement($listItem)) {
            // set the owning side to null (unless already changed)
            if ($listItem->getDistributor() === $this) {
                $listItem->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BasketItems>
     */
    public function getBasketItems(): Collection
    {
        return $this->basketItems;
    }

    public function addBasketItem(BasketItems $basketItem): self
    {
        if (!$this->basketItems->contains($basketItem)) {
            $this->basketItems[] = $basketItem;
            $basketItem->setDistributor($this);
        }

        return $this;
    }

    public function removeBasketItem(BasketItems $basketItem): self
    {
        if ($this->basketItems->removeElement($basketItem)) {
            // set the owning side to null (unless already changed)
            if ($basketItem->getDistributor() === $this) {
                $basketItem->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClinicProducts>
     */
    public function getClinicProducts(): Collection
    {
        return $this->clinicProducts;
    }

    public function addClinicProduct(ClinicProducts $clinicProduct): self
    {
        if (!$this->clinicProducts->contains($clinicProduct)) {
            $this->clinicProducts[] = $clinicProduct;
            $clinicProduct->setDistributor($this);
        }

        return $this;
    }

    public function removeClinicProduct(ClinicProducts $clinicProduct): self
    {
        if ($this->clinicProducts->removeElement($clinicProduct)) {
            // set the owning side to null (unless already changed)
            if ($clinicProduct->getDistributor() === $this) {
                $clinicProduct->setDistributor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AvailabilityTracker>
     */
    public function getAvailabilityTrackers(): Collection
    {
        return $this->availabilityTrackers;
    }

    public function addAvailabilityTracker(AvailabilityTracker $availabilityTracker): self
    {
        if (!$this->availabilityTrackers->contains($availabilityTracker)) {
            $this->availabilityTrackers[] = $availabilityTracker;
            $availabilityTracker->setDistributor($this);
        }

        return $this;
    }

    public function removeAvailabilityTracker(AvailabilityTracker $availabilityTracker): self
    {
        if ($this->availabilityTrackers->removeElement($availabilityTracker)) {
            // set the owning side to null (unless already changed)
            if ($availabilityTracker->getDistributor() === $this) {
                $availabilityTracker->setDistributor(null);
            }
        }

        return $this;
    }

    public function getPoNumberPrefix(): ?string
    {
        return $this->poNumberPrefix;
    }

    public function setPoNumberPrefix(?string $poNumberPrefix): self
    {
        $this->poNumberPrefix = $poNumberPrefix;

        return $this;
    }

    /**
     * @return Collection<int, ChatParticipants>
     */
    public function getChatParticipants(): Collection
    {
        return $this->chatParticipants;
    }

    public function addChatParticipant(ChatParticipants $chatParticipant): self
    {
        if (!$this->chatParticipants->contains($chatParticipant)) {
            $this->chatParticipants[] = $chatParticipant;
            $chatParticipant->setDistributor($this);
        }

        return $this;
    }

    public function removeChatParticipant(ChatParticipants $chatParticipant): self
    {
        if ($this->chatParticipants->removeElement($chatParticipant)) {
            // set the owning side to null (unless already changed)
            if ($chatParticipant->getDistributor() === $this) {
                $chatParticipant->setDistributor(null);
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
            $chatMessage->setDistributor($this);
        }

        return $this;
    }

    public function removeChatMessage(ChatMessages $chatMessage): self
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getDistributor() === $this) {
                $chatMessage->setDistributor(null);
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
            $notification->setDistributors($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getDistributors() === $this) {
                $notification->setDistributors(null);
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
            $orderStatus->setDistributor($this);
        }

        return $this;
    }

    public function removeOrderStatus(OrderStatus $orderStatus): self
    {
        if ($this->orderStatuses->removeElement($orderStatus)) {
            // set the owning side to null (unless already changed)
            if ($orderStatus->getDistributor() === $this) {
                $orderStatus->setDistributor(null);
            }
        }

        return $this;
    }
}
