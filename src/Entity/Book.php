<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getBooks", "getAuthor"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthor"])]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 1, max: 255, maxMessage: "Le titre doit faire entre {{ limit }} et {{ limit }} caractères")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["getBooks", "getAuthor"])]
    private ?string $coverText = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups(["getBooks"])]
    private ?Author $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCoverText(): ?string
    {
        return $this->coverText;
    }

    public function setCoverText(string $coverText): static
    {
        $this->coverText = $coverText;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): static
    {
        $this->author = $author;

        return $this;
    }
}
