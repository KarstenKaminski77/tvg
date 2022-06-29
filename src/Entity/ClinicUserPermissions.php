<?php

namespace App\Entity;

use App\Repository\ClinicUserPermissionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ClinicUserPermissionsRepository::class)
 */
class ClinicUserPermissions
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Clinics::class, inversedBy="clinicUserPermissions")
     */
    private $clinic;

    /**
     * @ORM\ManyToOne(targetEntity=ClinicUsers::class, inversedBy="clinicUserPermissions")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserPermissions::class, inversedBy="clinicUserPermissions")
     */
    private $permission;

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
        $this->setModified(new \DateTime());
        if ($this->getCreated() == null) {
            $this->setCreated(new \DateTime());
        }
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

    public function getUser(): ?ClinicUsers
    {
        return $this->user;
    }

    public function setUser(?ClinicUsers $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPermission(): ?UserPermissions
    {
        return $this->permission;
    }

    public function setPermission(?UserPermissions $permission): self
    {
        $this->permission = $permission;

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
