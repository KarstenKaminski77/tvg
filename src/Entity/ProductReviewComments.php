<?php

namespace App\Entity;

use App\Repository\ProductReviewCommentsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductReviewCommentsRepository::class)
 */
class ProductReviewComments
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ProductReviews::class, inversedBy="productReviewComments")
     */
    private $review;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="productReviewComments")
     */
    private $clinic;

    /**
     * @ORM\ManyToOne(targetEntity=ClinicUsers::class, inversedBy="productReviewComments")
     */
    private $clinicUser;

    /**
     * @ORM\Column(type="text")
     */
    private $comment;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

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

    public function getReview(): ?ProductReviews
    {
        return $this->review;
    }

    public function setReview(?ProductReviews $review): self
    {
        $this->review = $review;

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

    public function getClinicUser(): ?ClinicUsers
    {
        return $this->clinicUser;
    }

    public function setClinicUser(?ClinicUsers $clinicUser): self
    {
        $this->clinicUser = $clinicUser;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

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
