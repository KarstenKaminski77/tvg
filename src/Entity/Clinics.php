<?php

namespace App\Entity;

use App\Repository\ClinicsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClinicsRepository::class)
 */
class Clinics
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
    private $clinicName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=Addresses::class, mappedBy="clinic")
     */
    private $addresses;

    /**
     * @ORM\OneToMany(targetEntity=Distributors::class, mappedBy="clinic")
     */
    private $distributors;

    /**
     * @ORM\OneToMany(targetEntity=Baskets::class, mappedBy="clinic")
     */
    private $baskets;

    /**
     * @ORM\OneToMany(targetEntity=ClinicCommunicationMethods::class, mappedBy="clinic")
     */
    private $clinicCommunicationMethods;

    /**
     * @ORM\OneToMany(targetEntity=ClinicUsers::class, mappedBy="clinic", cascade={"persist"}))
     */
    private $clinicUsers;

    /**
     * @ORM\OneToMany(targetEntity=DistributorClinicPrices::class, mappedBy="clinic")
     */
    private $distributorClinicPrices;

    /**
     * @ORM\OneToMany(targetEntity=Orders::class, mappedBy="clinic")
     */
    private $orders;

    /**
     * @ORM\OneToMany(targetEntity=EventLog::class, mappedBy="clinic")
     */
    private $eventLogs;

    /**
     * @ORM\OneToMany(targetEntity=Lists::class, mappedBy="clinic")
     */
    private $lists;

    /**
     * @ORM\OneToMany(targetEntity=ProductNotes::class, mappedBy="clinic")
     */
    private $productNotes;

    /**
     * @ORM\OneToMany(targetEntity=AvailabilityTracker::class, mappedBy="clinic")
     */
    private $availabilityTrackers;

    /**
     * @ORM\OneToMany(targetEntity=ClinicProducts::class, mappedBy="clinic")
     */
    private $clinicProducts;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviewComments::class, mappedBy="clinic")
     */
    private $productReviewComments;

    /**
     * @ORM\OneToMany(targetEntity=Notifications::class, mappedBy="clinic")
     */
    private $notifications;

    /**
     * @ORM\OneToMany(targetEntity=ProductFavourites::class, mappedBy="clinic")
     */
    private $productFavourites;

    /**
     * @ORM\OneToMany(targetEntity=ChatParticipants::class, mappedBy="clinic")
     */
    private $chatParticipants;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $isoCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $intlCode;

    /**
     * @ORM\OneToMany(targetEntity=ClinicUserPermissions::class, mappedBy="clinic")
     */
    private $clinicUserPermissions;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }

        $this->addresses = new ArrayCollection();
        $this->baskets = new ArrayCollection();
        $this->distributors = new ArrayCollection();
        $this->clinicCommunicationMethods = new ArrayCollection();
        $this->clinicUsers = new ArrayCollection();
        $this->distributorClinicPrices = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->eventLogs = new ArrayCollection();
        $this->lists = new ArrayCollection();
        $this->productNotes = new ArrayCollection();
        $this->availabilityTrackers = new ArrayCollection();
        $this->clinicProducts = new ArrayCollection();
        $this->productReviewComments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->productFavourites = new ArrayCollection();
        $this->chatParticipants = new ArrayCollection();
        $this->clinicUserPermissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClinicName(): ?string
    {
        return $this->clinicName;
    }

    public function setClinicName(string $clinicName): self
    {
        $this->clinicName = $clinicName;

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
     * @return Collection|Addresses[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Addresses $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setClinic($this);
        }

        return $this;
    }

    public function removeAddress(Addresses $address): self
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getClinic() === $this) {
                $address->setClinic(null);
            }
        }

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
            $basket->setClinic($this);
        }

        return $this;
    }

    public function removeBasket(Baskets $basket): self
    {
        if ($this->baskets->removeElement($basket)) {
            // set the owning side to null (unless already changed)
            if ($basket->getClinic() === $this) {
                $basket->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ClinicCommunicationMethods[]
     */
    public function getClinicCommunicationMethods(): Collection
    {
        return $this->clinicCommunicationMethods;
    }

    public function addClinicCommunicationMethod(ClinicCommunicationMethods $clinicCommunicationMethod): self
    {
        if (!$this->clinicCommunicationMethods->contains($clinicCommunicationMethod)) {
            $this->clinicCommunicationMethods[] = $clinicCommunicationMethod;
            $clinicCommunicationMethod->setClinic($this);
        }

        return $this;
    }

    public function removeClinicCommunicationMethod(ClinicCommunicationMethods $clinicCommunicationMethod): self
    {
        if ($this->clinicCommunicationMethods->removeElement($clinicCommunicationMethod)) {
            // set the owning side to null (unless already changed)
            if ($clinicCommunicationMethod->getClinic() === $this) {
                $clinicCommunicationMethod->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ClinicUsers[]
     */
    public function getClinicUsers(): Collection
    {
        return $this->clinicUsers;
    }

    public function addClinicUser(ClinicUsers $clinicUser): self
    {
        if (!$this->clinicUsers->contains($clinicUser)) {
            $this->clinicUsers[] = $clinicUser;
            $clinicUser->setClinic($this);
        }

        return $this;
    }

    public function removeClinicUser(ClinicUsers $clinicUser): self
    {
        if ($this->clinicUsers->removeElement($clinicUser)) {
            // set the owning side to null (unless already changed)
            if ($clinicUser->getClinic() === $this) {
                $clinicUser->setClinic(null);
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
            $distributorClinicPrice->setClinic($this);
        }

        return $this;
    }

    public function removeDistributorClinicPrice(DistributorClinicPrices $distributorClinicPrice): self
    {
        if ($this->distributorClinicPrices->removeElement($distributorClinicPrice)) {
            // set the owning side to null (unless already changed)
            if ($distributorClinicPrice->getClinic() === $this) {
                $distributorClinicPrice->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Orders[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Orders $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setClinic($this);
        }

        return $this;
    }

    public function removeOrder(Orders $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getClinic() === $this) {
                $order->setClinic(null);
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
            $eventLog->setClinic($this);
        }

        return $this;
    }

    public function removeEventLog(EventLog $eventLog): self
    {
        if ($this->eventLogs->removeElement($eventLog)) {
            // set the owning side to null (unless already changed)
            if ($eventLog->getClinic() === $this) {
                $eventLog->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Lists[]
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    public function addList(Lists $list): self
    {
        if (!$this->lists->contains($list)) {
            $this->lists[] = $list;
            $list->setClinic($this);
        }

        return $this;
    }

    public function removeList(Lists $list): self
    {
        if ($this->lists->removeElement($list)) {
            // set the owning side to null (unless already changed)
            if ($list->getClinic() === $this) {
                $list->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductNotes[]
     */
    public function getProductNotes(): Collection
    {
        return $this->productNotes;
    }

    public function addProductNote(ProductNotes $productNote): self
    {
        if (!$this->productNotes->contains($productNote)) {
            $this->productNotes[] = $productNote;
            $productNote->setClinic($this);
        }

        return $this;
    }

    public function removeProductNote(ProductNotes $productNote): self
    {
        if ($this->productNotes->removeElement($productNote)) {
            // set the owning side to null (unless already changed)
            if ($productNote->getClinic() === $this) {
                $productNote->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AvailabilityTracker[]
     */
    public function getAvailabilityTrackers(): Collection
    {
        return $this->availabilityTrackers;
    }

    public function addAvailabilityTracker(AvailabilityTracker $availabilityTracker): self
    {
        if (!$this->availabilityTrackers->contains($availabilityTracker)) {
            $this->availabilityTrackers[] = $availabilityTracker;
            $availabilityTracker->setClinic($this);
        }

        return $this;
    }

    public function removeAvailabilityTracker(AvailabilityTracker $availabilityTracker): self
    {
        if ($this->availabilityTrackers->removeElement($availabilityTracker)) {
            // set the owning side to null (unless already changed)
            if ($availabilityTracker->getClinic() === $this) {
                $availabilityTracker->setClinic(null);
            }
        }

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
            $clinicProduct->setClinic($this);
        }

        return $this;
    }

    public function removeClinicProduct(ClinicProducts $clinicProduct): self
    {
        if ($this->clinicProducts->removeElement($clinicProduct)) {
            // set the owning side to null (unless already changed)
            if ($clinicProduct->getClinic() === $this) {
                $clinicProduct->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductReviewComments>
     */
    public function getProductReviewComments(): Collection
    {
        return $this->productReviewComments;
    }

    public function addProductReviewComment(ProductReviewComments $productReviewComment): self
    {
        if (!$this->productReviewComments->contains($productReviewComment)) {
            $this->productReviewComments[] = $productReviewComment;
            $productReviewComment->setClinic($this);
        }

        return $this;
    }

    public function removeProductReviewComment(ProductReviewComments $productReviewComment): self
    {
        if ($this->productReviewComments->removeElement($productReviewComment)) {
            // set the owning side to null (unless already changed)
            if ($productReviewComment->getClinic() === $this) {
                $productReviewComment->setClinic(null);
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
            $notification->setClinic($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getClinic() === $this) {
                $notification->setClinic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductFavourites>
     */
    public function getProductFavourites(): Collection
    {
        return $this->productFavourites;
    }

    public function addProductFavourite(ProductFavourites $productFavourite): self
    {
        if (!$this->productFavourites->contains($productFavourite)) {
            $this->productFavourites[] = $productFavourite;
            $productFavourite->setClinic($this);
        }

        return $this;
    }

    public function removeProductFavourite(ProductFavourites $productFavourite): self
    {
        if ($this->productFavourites->removeElement($productFavourite)) {
            // set the owning side to null (unless already changed)
            if ($productFavourite->getClinic() === $this) {
                $productFavourite->setClinic(null);
            }
        }

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
            $chatParticipant->setClinic($this);
        }

        return $this;
    }

    public function removeChatParticipant(ChatParticipants $chatParticipant): self
    {
        if ($this->chatParticipants->removeElement($chatParticipant)) {
            // set the owning side to null (unless already changed)
            if ($chatParticipant->getClinic() === $this) {
                $chatParticipant->setClinic(null);
            }
        }

        return $this;
    }

    public function getIsoCode(): ?string
    {
        return $this->isoCode;
    }

    public function setIsoCode(?string $isoCode): self
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    public function getIntlCode(): ?string
    {
        return $this->intlCode;
    }

    public function setIntlCode(?string $intlCode): self
    {
        $this->intlCode = $intlCode;

        return $this;
    }

    /**
     * @return Collection<int, ClinicUserPermissions>
     */
    public function getClinicUserPermissions(): Collection
    {
        return $this->clinicUserPermissions;
    }

    public function addClinicUserPermission(ClinicUserPermissions $clinicUserPermission): self
    {
        if (!$this->clinicUserPermissions->contains($clinicUserPermission)) {
            $this->clinicUserPermissions[] = $clinicUserPermission;
            $clinicUserPermission->setClinic($this);
        }

        return $this;
    }

    public function removeClinicUserPermission(ClinicUserPermissions $clinicUserPermission): self
    {
        if ($this->clinicUserPermissions->removeElement($clinicUserPermission)) {
            // set the owning side to null (unless already changed)
            if ($clinicUserPermission->getClinic() === $this) {
                $clinicUserPermission->setClinic(null);
            }
        }

        return $this;
    }
}
