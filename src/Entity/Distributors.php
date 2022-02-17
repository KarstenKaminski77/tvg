<?php

namespace App\Entity;

use App\Repository\DistributorsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=DistributorsRepository::class)
 */
class Distributors implements UserInterface, PasswordAuthenticatedUserInterface
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
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $logo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $about;

    /**
     * @ORM\Column(type="text")
     */
    private $operatingHours;

    /**
     * @ORM\Column(type="text")
     */
    private $refundPolicy;

    /**
     * @ORM\Column(type="text")
     */
    private $salesTaxPolicy;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isManufaturer;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $themeId;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
}
