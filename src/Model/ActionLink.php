<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Model;

final readonly class ActionLink
{
    /**
     * @param array<string, string> $attr
     */
    public function __construct(
        public Action $action,
        public string $url,
        public ?object $entity = null,
        public ?string $content = null,
        public array $attr = [],
    ) {
    }
}
