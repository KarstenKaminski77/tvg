<?php

namespace App\Entity;

use App\Repository\UserPermissionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserPermissionsRepository::class)
 */
class UserPermissions
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
    private $permission;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $info;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isClinic;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDistributor;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=ClinicUserPermissions::class, mappedBy="permission")
     */
    private $clinicUserPermissions;

    public function __construct()
    {
        $this->setModified(new \DateTime());
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime());
        }

        $this->clinicUserPermissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function isIsClinic(): ?bool
    {
        return $this->isClinic;
    }

    public function setIsClinic(?bool $isClinic): self
    {
        $this->isClinic = $isClinic;

        return $this;
    }

    public function getIsDistributor(): ?bool
    {
        return $this->isDistributor;
    }

    public function setIsDistributor(?bool $isDistributor): self
    {
        $this->isDistributor = $isDistributor;

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
            $clinicUserPermission->setPermission($this);
        }

        return $this;
    }

    public function removeClinicUserPermission(ClinicUserPermissions $clinicUserPermission): self
    {
        if ($this->clinicUserPermissions->removeElement($clinicUserPermission)) {
            // set the owning side to null (unless already changed)
            if ($clinicUserPermission->getPermission() === $this) {
                $clinicUserPermission->setPermission(null);
            }
        }

        return $this;
    }
}
