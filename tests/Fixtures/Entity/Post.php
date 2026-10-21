<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'post')]
class Post implements \Stringable
{
    public const string DRAFT = 'draft';
    public const string PUBLISHED = 'published';
    /** Locked posts cannot be edited nor deleted, see PostVoter */
    public const string LOCKED = 'locked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::DRAFT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
