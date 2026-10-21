<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Model;

use Symfony\Component\Routing\Route;

/**
 * Holds information about an action and its related route and template. Immutable: the route is described by plain
 * values and a new Route object is built on demand (see createRoute()), so that nothing that mutates a Route (route
 * collection prefixes, compilation cache...) can ever alter the action.
 *
 * Do not instantiate it directly, it is built by its admin (see {@see Admin}): the constructor must not call anything
 * on the admin but the getters of its scalar properties.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final readonly class Action
{
    /** @var list<string> */
    private array $routeVariables;

    /** @var array<string, mixed> */
    private array $routeDefaults;

    /**
     * @param array<string, mixed>  $defaults           route defaults, `_admin` and `_action` are added
     * @param array<string, string> $requirements
     * @param array<string, mixed>  $routeOptions
     * @param list<string>          $schemes
     * @param list<string>          $methods
     * @param array<string, mixed>  $formOptions
     * @param array<string, mixed>  $templateParameters
     * @param list<string>          $permissions
     * @param array<string, mixed>  $options            your own options for the action, not related to the route
     */
    public function __construct(
        private string $code,
        private Admin $admin,
        private string $path,
        array $defaults,
        private array $requirements,
        private array $routeOptions,
        private string $host,
        private array $schemes,
        private array $methods,
        private ?string $condition,
        private ?string $formType,
        private array $formOptions,
        private ?string $template,
        private array $templateParameters,
        private string $baseTemplate,
        private array $permissions,
        private array $options,
    ) {
        $this->routeDefaults = [...$defaults, '_admin' => $admin->getCode(), '_action' => $code];
        $this->routeVariables = $this->createRoute()->compile()->getVariables();
    }

    public function getRouteName(): string
    {
        return "sidus_admin.{$this->admin->getCode()}.{$this->code}";
    }

    /**
     * A new Route each call: it is yours to alter.
     */
    public function createRoute(): Route
    {
        return new Route(
            $this->admin->getPrefix().$this->path,
            $this->routeDefaults,
            $this->requirements,
            $this->routeOptions,
            $this->host,
            $this->schemes,
            $this->methods,
            $this->condition,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteDefaults(): array
    {
        return $this->routeDefaults;
    }

    /**
     * The placeholders of the route path
     *
     * @return list<string>
     */
    public function getRouteVariables(): array
    {
        return $this->routeVariables;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAdmin(): Admin
    {
        return $this->admin;
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

    /**
     * The form type of the action, the one of the admin when the action has none.
     */
    public function getFormType(): ?string
    {
        return $this->formType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateParameters(): array
    {
        return $this->templateParameters;
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
