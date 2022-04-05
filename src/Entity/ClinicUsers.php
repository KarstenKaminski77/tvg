<?php

namespace App\Entity;

use App\Repository\ClinicUsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=ClinicUsersRepository::class)
 */
class ClinicUsers implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="clinicUsers")
     */
    private $clinic;

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
    private $position;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

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
     * @ORM\OneToMany(targetEntity=ProductNotes::class, mappedBy="clinicUser")
     */
    private $productNotes;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviews::class, mappedBy="clinicUser")
     */
    private $productReviews;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPrimary;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reviewUsername;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviewLikes::class, mappedBy="clinicUser")
     */
    private $productReviewLikes;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviewComments::class, mappedBy="clinicUser")
     */
    private $productReviewComments;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }

        $this->productNotes = new ArrayCollection();
        $this->productReviews = new ArrayCollection();
        $this->productReviewLikes = new ArrayCollection();
        $this->productReviewComments = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

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
            $productNote->setClinicUser($this);
        }

        return $this;
    }

    public function removeProductNote(ProductNotes $productNote): self
    {
        if ($this->productNotes->removeElement($productNote)) {
            // set the owning side to null (unless already changed)
            if ($productNote->getClinicUser() === $this) {
                $productNote->setClinicUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductReviews[]
     */
    public function getProductReviews(): Collection
    {
        return $this->productReviews;
    }

    public function addProductReview(ProductReviews $productReview): self
    {
        if (!$this->productReviews->contains($productReview)) {
            $this->productReviews[] = $productReview;
            $productReview->setClinicUser($this);
        }

        return $this;
    }

    public function removeProductReview(ProductReviews $productReview): self
    {
        if ($this->productReviews->removeElement($productReview)) {
            // set the owning side to null (unless already changed)
            if ($productReview->getClinicUser() === $this) {
                $productReview->setClinicUser(null);
            }
        }

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
        // If you inventory any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function __toString(){

        return $this->getFirstName() .' '. $this->getLastName();
    }

    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

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

    public function getReviewUsername(): ?string
    {
        return $this->reviewUsername;
    }

    public function setReviewUsername(?string $reviewUsername): self
    {
        $this->reviewUsername = $reviewUsername;

        return $this;
    }

    /**
     * @return Collection<int, ProductReviewLikes>
     */
    public function getProductReviewLikes(): Collection
    {
        return $this->productReviewLikes;
    }

    public function addProductReviewLike(ProductReviewLikes $productReviewLike): self
    {
        if (!$this->productReviewLikes->contains($productReviewLike)) {
            $this->productReviewLikes[] = $productReviewLike;
            $productReviewLike->setClinicUser($this);
        }

        return $this;
    }

    public function removeProductReviewLike(ProductReviewLikes $productReviewLike): self
    {
        if ($this->productReviewLikes->removeElement($productReviewLike)) {
            // set the owning side to null (unless already changed)
            if ($productReviewLike->getClinicUser() === $this) {
                $productReviewLike->setClinicUser(null);
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
            $productReviewComment->setClinicUser($this);
        }

        return $this;
    }

    public function removeProductReviewComment(ProductReviewComments $productReviewComment): self
    {
        if ($this->productReviewComments->removeElement($productReviewComment)) {
            // set the owning side to null (unless already changed)
            if ($productReviewComment->getClinicUser() === $this) {
                $productReviewComment->setClinicUser(null);
            }
        }

        return $this;
    }
}
