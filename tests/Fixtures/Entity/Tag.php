<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An entity without __toString() and with a natural identifier, the second admin of the fixtures
 */
#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $slug = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $label = '';

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
