<?php

namespace App\Entity;

use App\Repository\DistributorUserPermissionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DistributorUserPermissionsRepository::class)
 */
class DistributorUserPermissions
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Distributors::class, inversedBy="distributorUserPermissions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $distributor;

    /**
     * @ORM\ManyToOne(targetEntity=DistributorUsers::class, inversedBy="distributorUserPermissions")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserPermissions::class, inversedBy="distributorUserPermissions")
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

    public function getDistributor(): ?Distributors
    {
        return $this->distributor;
    }

    public function setDistributor(?Distributors $distributor): self
    {
        $this->distributor = $distributor;

        return $this;
    }

    public function getUser(): ?DistributorUsers
    {
        return $this->user;
    }

    public function setUser(?DistributorUsers $user): self
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
