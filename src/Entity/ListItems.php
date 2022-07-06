<?php

namespace App\Entity;

use App\Repository\ListItemsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ListItemsRepository::class)
 */
class ListItems
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="listItems")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Distributors::class, inversedBy="listItems")
     */
    private $distributor;

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
     * @ORM\ManyToOne(targetEntity=Lists::class, inversedBy="listItems")
     */
    private $list;

    /**
     * @ORM\ManyToOne(targetEntity=DistributorProducts::class, inversedBy="listItems")
     */
    private $distributorProduct;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty;

    public function __construct()
    {
        $this->setCreated(new \DateTime());
        if ($this->getModified() == null) {
            $this->setModified(new \DateTime());
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

        return $this;
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

    public function getList(): ?Lists
    {
        return $this->list;
    }

    public function setList(?Lists $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function getDistributorProduct(): ?DistributorProducts
    {
        return $this->distributorProduct;
    }

    public function setDistributorProduct(?DistributorProducts $distributorProduct): self
    {
        $this->distributorProduct = $distributorProduct;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;

        return $this;
    }
}
