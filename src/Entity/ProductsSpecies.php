<?php

namespace App\Entity;

use App\Repository\ProductsSpeciesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductsSpeciesRepository::class)
 */
class ProductsSpecies
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=products::class, inversedBy="productSpecies")
     */
    private $products;

    /**
     * @ORM\ManyToOne(targetEntity=Species::class, inversedBy="productsSpecies")
     */
    private $species;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProducts(): ?products
    {
        return $this->products;
    }

    public function setProducts(?products $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function setSpecies(?Species $species): self
    {
        $this->species = $species;

        return $this;
    }
}
