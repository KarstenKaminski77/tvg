<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ORM\Mapping as ORM;

/**
 * Blog
 *
 * @ORM\Table(name="blog",indexes={
 *          @ORM\Index(name="title_idx", columns={"title"}, flags={"fulltext"}),
 *          @ORM\Index(name="keyword_1_idx", columns={"keyword_1"}, flags={"fulltext"}),
 *          @ORM\Index(name="keyword_2_idx", columns={"keyword_2"}, flags={"fulltext"}),
 *          @ORM\Index(name="keyword_3_idx", columns={"keyword_3"}, flags={"fulltext"}),
 *        })
 * @ORM\Entity(repositoryClass=App\Repository\BlogRepository::class)
 * @Vich\Uploadable
 */
class Blog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * Relation with category entity
     * @var blogCategory
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Category", inversedBy="blog", cascade={"remove"})
     * @ORM\JoinTable(name="category_blog")
     */
    protected $blogCategory;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @Vich\UploadableField(mapping="blog", fileNameProperty="image")
     * @var File
     */
    private $blogImage;

    /**
     * @var string|null
     *
     * @ORM\Column(name="thumbnail", type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
     * @Vich\UploadableField(mapping="blog", fileNameProperty="thumbnail")
     * @var File
     */
    private $blogThumbnail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string|null
     *
     * @ORM\Column(name="alt", type="string", length=255, nullable=true)
     */
    private $alt;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", length=65535, nullable=true)
     */
    private $body;

    /**
     * @var string|null
     *
     * @ORM\Column(name="meta_title", type="string", length=60, nullable=true)
     */
    private $metaTitle;

    /**
     * @var string|null
     *
     * @ORM\Column(name="meta_description", type="text", length=160, nullable=true)
     */
    private $metaDescription;

    /**
     * @var string|null
     *
     * @ORM\Column(name="keyword_1", type="string", length=50, nullable=true)
     */
    private $keyword1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="keyword_2", type="string", length=50, nullable=true)
     */
    private $keyword2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="keyword_3", type="string", length=50, nullable=true)
     */
    private $keyword3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    /**
     * @var string|null
     *
     * @ORM\Column(name="infographic_1", type="text", length=60, nullable=true)
     */
    private $infographic1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="infographic_2", type="text", length=60, nullable=true)
     */
    private $infographic2;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_promotion", type="boolean", length=11, nullable=true)
     */
    private $isPromotion = '0';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_home_page", type="boolean", length=11, nullable=true)
     */
    private $isHomePage;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_featured", type="boolean", length=11, nullable=true)
     */
    private $isFeatured;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_updated", type="datetime", length=255, nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $lastUpdated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_date", type="datetime", nullable=true)
     */
    private $displayDate;

    public function __construct()
    {
        $this->blogCategory = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getKeyword1(): ?string
    {
        return $this->keyword1;
    }

    public function setKeyword1(?string $keyword1): self
    {
        $this->keyword1 = $keyword1;

        return $this;
    }

    public function getKeyword2(): ?string
    {
        return $this->keyword2;
    }

    public function setKeyword2(?string $keyword2): self
    {
        $this->keyword2 = $keyword2;

        return $this;
    }

    public function getKeyword3(): ?string
    {
        return $this->keyword3;
    }

    public function setKeyword3(?string $keyword3): self
    {
        $this->keyword3 = $keyword3;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getInfographic1(): ?string
    {
        return $this->infographic1;
    }

    public function setInfographic1(?string $infographic1): self
    {
        $this->infographic1 = $infographic1;

        return $this;
    }

    public function getInfographic2(): ?string
    {
        return $this->infographic2;
    }

    public function setInfographic2(?string $infographic2): self
    {
        $this->infographic2 = $infographic2;

        return $this;
    }

    public function getIsPromotion(): ?bool
    {
        return $this->isPromotion;
    }

    public function setIsPromotion(?bool $isPromotion): self
    {
        $this->isPromotion = $isPromotion;

        return $this;
    }

    public function getIsHomePage(): ?bool
    {
        return $this->isHomePage;
    }

    public function setIsHomePage(?bool $isHomePage): self
    {
        $this->isHomePage = $isHomePage;

        return $this;
    }

    public function getIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(?bool $isFeatured): self
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getDisplayDate(): ?\DateTimeInterface
    {
        return $this->displayDate;
    }

    public function setDisplayDate(?\DateTimeInterface $displayDate): self
    {
        $this->displayDate = $displayDate;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getBlogCategory(): Collection
    {
        return $this->blogCategory;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategory(): Collection
    {
        return $category = $this->blogCategory;
    }

    public function addBlogCategory(Category $blogCategory): self
    {
        if (!$this->blogCategory->contains($blogCategory)) {
            $this->blogCategory[] = $blogCategory;
            $blogCategory->addBlog($this);
        }

        return $this;
    }

    public function removeBlogCategory(Category $blogCategory): self
    {
        if ($this->blogCategory->removeElement($blogCategory)) {
            $blogCategory->removeBlog($this);
        }

        return $this;
    }

    public function setBlogImage(File $image = null)
    {
        $this->blogImage = $image;
    }
    public function getBlogImage()
    {
        return $this->blogImage;
    }

    public function setBlogThumbnail(File $thumbnail = null)
    {
        $this->blogThumbnail = $thumbnail;
    }
    public function getBlogThumbnail()
    {
        return $this->blogThumbnail;
    }

    /**
     * @return string
     */
   /* public function __toString(){

        return $this->getBlog();
    }*/
}
