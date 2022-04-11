<?php

namespace App\Entity;

use App\Repository\ManufacturersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ManufacturersRepository::class)
 */
class Manufacturers
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
     * @ORM\ManyToMany(targetEntity="App\Entity\Products", mappedBy="productManufacturers")
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
     * @ORM\OneToMany(targetEntity=ProductManufacturers::class, mappedBy="manufacturers")
     */
    private $productManufacturers;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
        $this->productManufacturers = new ArrayCollection();
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
     * @return Collection<int, ProductManufacturers>
     */
    public function getProductManufacturers(): Collection
    {
        return $this->productManufacturers;
    }

    public function addProductManufacturer(ProductManufacturers $productManufacturer): self
    {
        if (!$this->productManufacturers->contains($productManufacturer)) {
            $this->productManufacturers[] = $productManufacturer;
            $productManufacturer->setManufacturers($this);
        }

        return $this;
    }

    public function removeProductManufacturer(ProductManufacturers $productManufacturer): self
    {
        if ($this->productManufacturers->removeElement($productManufacturer)) {
            // set the owning side to null (unless already changed)
            if ($productManufacturer->getManufacturers() === $this) {
                $productManufacturer->setManufacturers(null);
            }
        }

        return $this;
    }
}
