<?php

namespace App\Entity;

use App\Repository\SpeciesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SpeciesRepository::class)
 */
class Species
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Relation with Client Role Entity
     * @var int|null
     * @ORM\ManyToMany(targetEntity="App\Entity\Products", mappedBy="productsSpecies")
     */
    private $products;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity=ProductsSpecies::class, mappedBy="species")
     */
    private $productsSpecies;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
        $this->productsSpecies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|Products[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProducts(Products $products): self
    {
        if (!$this->products->contains($products)) {
            $this->products[] = $products;
        }

        return $this;
    }

    public function removeProducts(Products $products): self
    {
        $this->products->removeElement($products);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(){

        return $this->getName();
    }

    /**
     * @return Collection<int, ProductsSpecies>
     */
    public function getProductsSpecies(): Collection
    {
        return $this->productsSpecies;
    }

    public function addProductsSpecies(ProductsSpecies $productsSpecies): self
    {
        if (!$this->productsSpecies->contains($productsSpecies)) {
            $this->productsSpecies[] = $productsSpecies;
            $productsSpecies->setSpecies($this);
        }

        return $this;
    }

    public function removeProductsSpecies(ProductsSpecies $productsSpecies): self
    {
        if ($this->productsSpecies->removeElement($productsSpecies)) {
            // set the owning side to null (unless already changed)
            if ($productsSpecies->getSpecies() === $this) {
                $productsSpecies->setSpecies(null);
            }
        }

        return $this;
    }
}
