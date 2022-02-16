<?php

namespace App\Entity;

use App\Repository\CommunicationMethodsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommunicationMethodsRepository::class)
 */
class CommunicationMethods
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
    private $method;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=ClinicCommunicationMethods::class, mappedBy="communicationMethod")
     */
    private $clinicCommunicationMethods;

    /**
     * @ORM\OneToMany(targetEntity=AvailabilityTracker::class, mappedBy="communication")
     */
    private $availabilityTrackers;

    public function __construct()
    {
        $this->clinicCommunicationMethods = new ArrayCollection();
        $this->availabilityTrackers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

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
            $clinicCommunicationMethod->setCommunicationMethod($this);
        }

        return $this;
    }

    public function removeClinicCommunicationMethod(ClinicCommunicationMethods $clinicCommunicationMethod): self
    {
        if ($this->clinicCommunicationMethods->removeElement($clinicCommunicationMethod)) {
            // set the owning side to null (unless already changed)
            if ($clinicCommunicationMethod->getCommunicationMethod() === $this) {
                $clinicCommunicationMethod->setCommunicationMethod(null);
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
}
