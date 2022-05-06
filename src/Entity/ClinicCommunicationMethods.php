<?php

namespace App\Entity;

use App\Repository\ClinicCommunicationMethodsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClinicCommunicationMethodsRepository::class)
 */
class ClinicCommunicationMethods
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="clinicCommunicationMethods")
     */
    private $clinic;

    /**
     * @ORM\ManyToOne(targetEntity=CommunicationMethods::class, inversedBy="clinicCommunicationMethods")
     */
    private $communicationMethod;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $sendTo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $IsoCode;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $intlCode;

    /**
     * @ORM\OneToMany(targetEntity=AvailabilityTracker::class, mappedBy="communication")
     */
    private $availabilityTrackers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $isDefault;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
        $this->availabilityTrackers = new ArrayCollection();
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

    public function getCommunicationMethod(): ?CommunicationMethods
    {
        return $this->communicationMethod;
    }

    public function setCommunicationMethod(?CommunicationMethods $communicationMethod): self
    {
        $this->communicationMethod = $communicationMethod;

        return $this;
    }

    public function getSendTo(): ?string
    {
        return $this->sendTo;
    }

    public function setSendTo(string $sendTo): self
    {
        $this->sendTo = $sendTo;

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

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsoCode(): ?string
    {
        return $this->IsoCode;
    }

    public function setIsoCode(?string $IsoCode): self
    {
        $this->IsoCode = $IsoCode;

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
            $availabilityTracker->setCommunication($this);
        }

        return $this;
    }

    public function removeAvailabilityTracker(AvailabilityTracker $availabilityTracker): self
    {
        if ($this->availabilityTrackers->removeElement($availabilityTracker)) {
            // set the owning side to null (unless already changed)
            if ($availabilityTracker->getCommunication() === $this) {
                $availabilityTracker->setCommunication(null);
            }
        }

        return $this;
    }

    public function getIsDefault(): ?int
    {
        return $this->isDefault;
    }

    public function setIsDefault(?int $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
