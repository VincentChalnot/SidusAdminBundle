<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Routing\Route;

/**
 * Validates the raw (already structurally valid) admin configuration and turns it into the named constructor
 * arguments of {@see \Sidus\AdminBundle\Model\Admin}: applies the defaults of the bundle and of the admins to their
 * actions, the theme to the templates, and resolves the controller of every action. Pure: it never touches the
 * container, the existence of a controller is asked to the closure given to the constructor.
 *
 * Every failure is an {@see InvalidConfigurationException} naming the admin and the action: it happens when the
 * container is compiled, never at request time.
 */
final readonly class AdminConfigurationResolver
{
    public const string DEFAULT_BASE_TEMPLATE = '@SidusAdmin/base.html.twig';
    public const string DEFAULT_TEMPLATE_PATTERN = '@SidusAdmin/Action/{{action}}.{{format}}.twig';
    public const string NAMESPACE_PREFIX = '@SidusAdmin/';

    /** @var list<string> */
    public const array THEMES = ['bulma_1'];

    private const string CODE_PATTERN = '/^[A-Za-z][A-Za-z0-9_]*$/';
    private const array CONTROLLER_VARIABLES = ['{{admin}}', '{{Admin}}', '{{action}}', '{{Action}}'];
    private const array TEMPLATE_VARIABLES = ['{{admin}}', '{{Admin}}', '{{action}}', '{{Action}}', '{{format}}'];

    /**
     * @param \Closure(string): bool $controllerExists whether a controller reference (service id, class name or
     *                                                 class::method) can be resolved
     */
    public function __construct(
        private ?string $theme,
        private ?string $baseTemplate,
        private \Closure $controllerExists,
    ) {
    }

    /**
     * @param array<string, array<string, mixed>> $configurations the admins by code
     *
     * @return array<string, array<string, mixed>> the named arguments of Admin by admin code
     */
    public function resolve(array $configurations): array
    {
        $resolved = [];
        foreach ($configurations as $code => $configuration) {
            $code = (string) $code;
            $this->assertCode($code);
            $resolved[$code] = $this->resolveAdmin($code, $configuration);
        }
        $this->assertNoCollision($resolved);

        return $resolved;
    }

    /**
     * @param array<string, mixed> $c
     *
     * @return array<string, mixed>
     */
    private function resolveAdmin(string $code, array $c): array
    {
        $entity = $c['entity'];
        if (!class_exists($entity) && !interface_exists($entity)) {
            throw $this->error($code, null, "the entity '{$entity}' is neither an existing class nor an interface");
        }
        if (null !== $c['form_type'] && !class_exists($c['form_type'])) {
            throw $this->error($code, null, "the form_type '{$c['form_type']}' is not an existing class");
        }
        $controllerPattern = $this->assertPatterns($code, null, 'controller_pattern', $c['controller_pattern'], self::CONTROLLER_VARIABLES);
        $templatePattern = $this->assertPatterns($code, null, 'template_pattern', $c['template_pattern'], self::TEMPLATE_VARIABLES);
        $baseTemplate = $c['base_template'] ?? $this->baseTemplate ?? $this->defaultBaseTemplate();

        $actions = [];
        foreach ($c['actions'] as $actionCode => $action) {
            $actions[(string) $actionCode] = $this->resolveAction($code, (string) $actionCode, $action, $c, $controllerPattern, $baseTemplate);
        }

        return [
            '$code' => $code,
            '$entity' => $entity,
            '$prefix' => $c['prefix'],
            '$formType' => $c['form_type'],
            '$baseTemplate' => $baseTemplate,
            '$controllerPattern' => $controllerPattern,
            '$templatePattern' => $this->themed([] === $templatePattern ? [self::DEFAULT_TEMPLATE_PATTERN] : $templatePattern),
            '$options' => $c['options'],
            '$permissions' => array_values($c['permissions']),
            '$actions' => $actions,
        ];
    }

    /**
     * @param array<string, mixed> $a      the action
     * @param array<string, mixed> $admin  the admin
     * @param list<string>         $controllerPattern
     *
     * @return array<string, mixed> the named arguments of Action but the code and the admin
     */
    private function resolveAction(string $adminCode, string $code, array $a, array $admin, array $controllerPattern, string $baseTemplate): array
    {
        if (!preg_match(self::CODE_PATTERN, $code)) {
            throw $this->error($adminCode, $code, 'the action code must start with a letter and contain only letters, digits and underscores');
        }
        if (!str_starts_with($a['path'], '/')) {
            throw $this->error($adminCode, $code, "the path '{$a['path']}' must start with a slash, it is appended to the prefix of the admin");
        }
        if (null !== $a['form_type'] && !class_exists($a['form_type'])) {
            throw $this->error($adminCode, $code, "the form_type '{$a['form_type']}' is not an existing class");
        }

        $defaults = $a['defaults'];
        foreach (['_admin', '_action'] as $reserved) {
            if (array_key_exists($reserved, $defaults)) {
                throw $this->error($adminCode, $code, "the route default '{$reserved}' is reserved");
            }
        }
        if (isset($defaults['_controller_pattern'])) {
            $patterns = $this->assertPatterns($adminCode, $code, 'defaults._controller_pattern', (array) $defaults['_controller_pattern'], self::CONTROLLER_VARIABLES);
        } else {
            $patterns = $controllerPattern;
        }
        unset($defaults['_controller_pattern']);
        if (empty($defaults['_controller'])) {
            $defaults['_controller'] = $this->resolveController($adminCode, $code, $patterns);
        }

        $resolved = [
            'path' => $a['path'],
            'defaults' => $defaults,
            'requirements' => $a['requirements'],
            'routeOptions' => $a['route_options'],
            'host' => $a['host'],
            'schemes' => array_values($a['schemes']),
            'methods' => array_values(array_map('strtoupper', $a['methods'])),
            'condition' => $a['condition'],
            'formType' => $a['form_type'] ?? $admin['form_type'],
            'formOptions' => $a['form_options'],
            'template' => $a['template'],
            'templateParameters' => $a['template_parameters'],
            'baseTemplate' => $a['base_template'] ?? $baseTemplate,
            'permissions' => array_values($a['permissions']),
            'options' => $a['options'],
        ];

        try {
            new Route(
                $admin['prefix'].$resolved['path'],
                $resolved['defaults'],
                $resolved['requirements'],
                $resolved['routeOptions'],
                $resolved['host'],
                $resolved['schemes'],
                $resolved['methods'],
                $resolved['condition'],
            )->compile();
        } catch (\LogicException|\InvalidArgumentException $exception) {
            throw $this->error($adminCode, $code, 'invalid route: '.$exception->getMessage(), $exception);
        }

        return $resolved;
    }

    /**
     * @param list<string> $patterns
     */
    private function resolveController(string $adminCode, string $code, array $patterns): string
    {
        if ([] === $patterns) {
            throw $this->error($adminCode, $code, "you must configure either the 'defaults._controller' option of the action or the 'controller_pattern' option of the admin");
        }

        $tried = [];
        foreach ($patterns as $pattern) {
            $candidate = strtr($pattern, [
                '{{admin}}' => lcfirst($adminCode),
                '{{Admin}}' => ucfirst($adminCode),
                '{{action}}' => lcfirst($code),
                '{{Action}}' => ucfirst($code),
            ]);
            if (($this->controllerExists)($candidate)) {
                return $candidate;
            }
            $tried[] = $candidate;
        }

        throw $this->error(
            $adminCode,
            $code,
            'no controller found for the controller_pattern ['.implode(', ', $patterns).'], tried: '.implode(', ', $tried)
            .'. A controller is a service id, or the name of a class without constructor arguments, optionally followed by ::method',
        );
    }

    /**
     * @param list<string> $patterns
     * @param list<string> $allowed
     *
     * @return list<string>
     */
    private function assertPatterns(string $adminCode, ?string $actionCode, string $option, array $patterns, array $allowed): array
    {
        foreach ($patterns as $pattern) {
            if (!is_string($pattern)) {
                throw $this->error($adminCode, $actionCode, "'{$option}' must be a list of strings");
            }
            if (preg_match_all('/\{\{[^{}]*\}\}/', $pattern, $matches)) {
                foreach ($matches[0] as $variable) {
                    if (!in_array($variable, $allowed, true)) {
                        throw $this->error($adminCode, $actionCode, "unknown variable {$variable} in the {$option} '{$pattern}', available variables: ".implode(', ', $allowed));
                    }
                }
            }
        }

        return array_values($patterns);
    }

    /**
     * Inserts the theme's variant of every pattern in the namespace of the bundle in front of it.
     *
     * @param list<string> $patterns
     *
     * @return list<string>
     */
    private function themed(array $patterns): array
    {
        if (null === $this->theme) {
            return $patterns;
        }
        $themed = [];
        foreach ($patterns as $pattern) {
            if (str_starts_with($pattern, self::NAMESPACE_PREFIX)) {
                $themed[] = self::NAMESPACE_PREFIX.'theme/'.$this->theme.'/'.substr($pattern, \strlen(self::NAMESPACE_PREFIX));
            }
            $themed[] = $pattern;
        }

        return $themed;
    }

    private function defaultBaseTemplate(): string
    {
        return null === $this->theme
            ? self::DEFAULT_BASE_TEMPLATE
            : self::NAMESPACE_PREFIX.'theme/'.$this->theme.'/'.substr(self::DEFAULT_BASE_TEMPLATE, \strlen(self::NAMESPACE_PREFIX));
    }

    private function assertCode(string $code): void
    {
        if (!preg_match(self::CODE_PATTERN, $code)) {
            throw $this->error($code, null, 'the admin code must start with a letter and contain only letters, digits and underscores (it is used in route and form names)');
        }
    }

    /**
     * @param array<string, array<string, mixed>> $resolved
     */
    private function assertNoCollision(array $resolved): void
    {
        $codes = [];
        $routes = [];
        foreach ($resolved as $code => $admin) {
            $lower = strtolower($code);
            if (isset($codes[$lower])) {
                throw new InvalidConfigurationException("The admins '{$codes[$lower]}' and '{$code}' only differ by case: admin codes must be unique regardless of the case (the {{admin}} variable of the patterns lowercases the first letter).");
            }
            $codes[$lower] = $code;

            foreach ($admin['$actions'] as $actionCode => $action) {
                $path = '/'.ltrim($admin['$prefix'].$action['path'], '/');
                $key = $action['host'].'|'.$path.'|'.$action['condition'];
                foreach ($routes[$key] ?? [] as [$otherAdmin, $otherAction, $otherMethods]) {
                    if ([] === $otherMethods || [] === $action['methods'] || [] !== array_intersect($otherMethods, $action['methods'])) {
                        throw new InvalidConfigurationException("The action '{$otherAdmin}.{$otherAction}' and the action '{$code}.{$actionCode}' have the same route path '{$path}'"
                            .('' !== $action['host'] ? " on the host '{$action['host']}'" : '').': one of them would never match. Change the prefix of an admin or the path of an action, or distinguish them with the methods.');
                    }
                }
                $routes[$key][] = [$code, $actionCode, $action['methods']];
            }
        }
    }

    private function error(string $adminCode, ?string $actionCode, string $message, ?\Throwable $previous = null): InvalidConfigurationException
    {
        $path = null === $actionCode ? "sidus_admin.configurations.{$adminCode}" : "sidus_admin.configurations.{$adminCode}.actions.{$actionCode}";

        return new InvalidConfigurationException("Invalid configuration for \"{$path}\": {$message}.", 0, $previous);
    }
}
