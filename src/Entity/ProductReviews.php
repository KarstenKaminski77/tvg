<?php

namespace App\Entity;

use App\Repository\ProductReviewsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductReviewsRepository::class)
 */
class ProductReviews
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="productReviews")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=ClinicUsers::class, inversedBy="productReviews")
     */
    private $clinicUser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $review;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $clinic;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $likes;

    /**
     * @ORM\Column(type="integer")
     */
    private $rating;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $position;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviewLikes::class, mappedBy="productReview")
     */
    private $productReviewLikes;

    /**
     * @ORM\OneToMany(targetEntity=ProductReviewComments::class, mappedBy="review")
     */
    private $productReviewComments;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
        $this->productReviewLikes = new ArrayCollection();
        $this->productReviewComments = new ArrayCollection();
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

    public function getClinicUser(): ?ClinicUsers
    {
        return $this->clinicUser;
    }

    public function setClinicUser(?ClinicUsers $clinicUser): self
    {
        $this->clinicUser = $clinicUser;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getReview(): ?string
    {
        return $this->review;
    }

    public function setReview(string $review): self
    {
        $this->review = $review;

        return $this;
    }

    public function getClinic(): ?string
    {
        return $this->clinic;
    }

    public function setClinic(string $clinic): self
    {
        $this->clinic = $clinic;

        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): self
    {
        $this->likes = $likes;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

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

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

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
            $productReviewLike->setProductReview($this);
        }

        return $this;
    }

    public function removeProductReviewLike(ProductReviewLikes $productReviewLike): self
    {
        if ($this->productReviewLikes->removeElement($productReviewLike)) {
            // set the owning side to null (unless already changed)
            if ($productReviewLike->getProductReview() === $this) {
                $productReviewLike->setProductReview(null);
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
            $productReviewComment->setReview($this);
        }

        return $this;
    }

    public function removeProductReviewComment(ProductReviewComments $productReviewComment): self
    {
        if ($this->productReviewComments->removeElement($productReviewComment)) {
            // set the owning side to null (unless already changed)
            if ($productReviewComment->getReview() === $this) {
                $productReviewComment->setReview(null);
            }
        }

        return $this;
    }
}
