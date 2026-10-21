<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Registry;

use Sidus\AdminBundle\Model\Admin;
use UnexpectedValueException;

/**
 * The admins by code, as configured. Read-only.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final readonly class AdminRegistry
{
    /**
     * @param array<string, Admin> $admins
     */
    public function __construct(private array $admins)
    {
    }

    /**
     * @return array<string, Admin>
     */
    public function all(): array
    {
        return $this->admins;
    }

    public function get(string $code): Admin
    {
        return $this->admins[$code] ?? throw new UnexpectedValueException(
            "No admin with code '{$code}', available admins: ".implode(', ', array_keys($this->admins))
        );
    }

    public function has(string $code): bool
    {
        return isset($this->admins[$code]);
    }
}
