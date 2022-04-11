<?php

namespace App\Entity;

use App\Repository\ProductManufacturersRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductManufacturersRepository::class)
 */
class ProductManufacturers
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="productManufacturers")
     */
    private $products;

    /**
     * @ORM\ManyToOne(targetEntity=Manufacturers::class, inversedBy="productManufacturers")
     */
    private $manufacturers;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProducts(): ?Products
    {
        return $this->products;
    }

    public function setProducts(?Products $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function getManufacturers(): ?Manufacturers
    {
        return $this->manufacturers;
    }

    public function setManufacturers(?Manufacturers $manufacturers): self
    {
        $this->manufacturers = $manufacturers;

        return $this;
    }
}
