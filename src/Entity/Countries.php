<?php

namespace App\Entity;

use App\Repository\CountriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CountriesRepository::class)
 */
class Countries
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Distributors::class, mappedBy="addressCountry")
     */
    private $distributors;

    public function __construct()
    {
        $this->distributors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Distributors>
     */
    public function getDistributors(): Collection
    {
        return $this->distributors;
    }

    public function addDistributor(Distributors $distributor): self
    {
        if (!$this->distributors->contains($distributor)) {
            $this->distributors[] = $distributor;
            $distributor->setAddressCountry($this);
        }

        return $this;
    }

    public function removeDistributor(Distributors $distributor): self
    {
        if ($this->distributors->removeElement($distributor)) {
            // set the owning side to null (unless already changed)
            if ($distributor->getAddressCountry() === $this) {
                $distributor->setAddressCountry(null);
            }
        }

        return $this;
    }
}
