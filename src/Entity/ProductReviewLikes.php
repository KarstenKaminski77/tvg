<?php

namespace App\Entity;

use App\Repository\ProductReviewLikesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductReviewLikesRepository::class)
 */
class ProductReviewLikes
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=ProductReviews::class, inversedBy="productReviewLikes")
     */
    private $productReview;

    /**
     * @ORM\ManyToOne(targetEntity=ClinicUsers::class, inversedBy="productReviewLikes")
     */
    private $clinicUser;

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

    public function getProductReview(): ?ProductReviews
    {
        return $this->productReview;
    }

    public function setProductReview(?ProductReviews $productReview): self
    {
        $this->productReview = $productReview;

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
}
