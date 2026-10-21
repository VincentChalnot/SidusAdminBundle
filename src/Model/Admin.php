<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Model;

use UnexpectedValueException;

/**
 * The admin serves as an action holder and is attached to an entity. Immutable: built once from the compiled
 * configuration, it holds no request state (the current admin and action are request attributes, see AdminContext).
 *
 * The admin builds its own actions in its constructor and hands them a reference to itself: this is how the
 * Admin <-> Action cross reference exists without any setter. The consequence: an Action constructor must only read
 * the admin properties that are promoted below (all initialized before the actions are built) and never call
 * getActions()/getAction()/hasAction()/hasRoute().
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final readonly class Admin
{
    /** @var array<string, Action> */
    private array $actions;

    /**
     * @param list<string>                         $controllerPattern
     * @param list<string>                         $templatePattern
     * @param array<string, mixed>                 $options
     * @param list<string>                         $permissions
     * @param array<string, array<string, mixed>>  $actions            normalized action configuration by code, the
     *                                                                 named arguments of {@see Action::__construct()}
     *                                                                 but the code and the admin
     */
    public function __construct(
        private string $code,
        private string $entity,
        private string $prefix,
        private ?string $formType,
        private string $baseTemplate,
        private array $controllerPattern,
        private array $templatePattern,
        private array $options,
        private array $permissions,
        array $actions,
    ) {
        $built = [];
        foreach ($actions as $actionCode => $actionConfiguration) {
            $built[$actionCode] = new Action(...$actionConfiguration, code: (string) $actionCode, admin: $this);
        }
        $this->actions = $built;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return list<string>
     */
    public function getControllerPattern(): array
    {
        return $this->controllerPattern;
    }

    /**
     * @return list<string>
     */
    public function getTemplatePattern(): array
    {
        return $this->templatePattern;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return array<string, Action>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getAction(string $code): Action
    {
        return $this->actions[$code] ?? throw new UnexpectedValueException(
            "No action with code '{$code}' for admin '{$this->code}', available actions: ".implode(', ', array_keys($this->actions))
        );
    }

    public function hasAction(string $code): bool
    {
        return isset($this->actions[$code]);
    }

    public function hasRoute(string $route): bool
    {
        foreach ($this->actions as $action) {
            if ($action->getRouteName() === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * Class (or interface) name of the entity
     *
     * @return class-string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $option, mixed $default = null): mixed
    {
        return $this->options[$option] ?? $default;
    }

    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    public function getBaseTemplate(): string
    {
        return $this->baseTemplate;
    }

    /**
     * @return list<string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
