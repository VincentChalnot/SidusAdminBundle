<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Templating;

use Psr\Log\LoggerInterface;
use Sidus\AdminBundle\Admin\Action;

/**
 * Resolve templates based on admin configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class TemplateResolver implements TemplateResolverInterface
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $globalFallbackTemplate;

    /**
     * @param \Twig_Environment $twig
     * @param string            $globalFallbackTemplate
     * @param LoggerInterface   $logger
     */
    public function __construct(
        \Twig_Environment $twig,
        $globalFallbackTemplate,
        LoggerInterface $logger
    ) {
        $this->twig = $twig;
        $this->globalFallbackTemplate = $globalFallbackTemplate;
        $this->logger = $logger;
    }

    /**
     * @param Action $action
     * @param string $format
     *
     * @return \Twig_Template
     */
    public function getTemplate(Action $action, $format = 'html'): \Twig_Template
    {
        $admin = $action->getAdmin();
        if ($action->getTemplate()) {
            // If the template was specified, do not try to fallback
            return $this->twig->loadTemplate($action->getTemplate());
        }

        // Priority to new template_pattern system:
        if (\count($admin->getTemplatePattern()) > 0) {
            foreach ($admin->getTemplatePattern() as $templatePattern) {
                $template = strtr(
                    $templatePattern,
                    [
                        '{{admin}}' => lcfirst($admin->getCode()),
                        '{{Admin}}' => ucfirst($admin->getCode()),
                        '{{action}}' => lcfirst($action->getCode()),
                        '{{Action}}' => ucfirst($action->getCode()),
                        '{{format}}' => $format,
                    ]
                );
                try {
                    return $this->twig->loadTemplate($template);
                } catch (\Twig_Error_Loader $mainError) {
                    continue;
                }
            }

            $flattened = implode(', ', $admin->getTemplatePattern());
            throw new \RuntimeException(
                "Unable to resolve any valid template for the template_pattern configuration: {$flattened}"
            );
        }

        $template = "{$action->getCode()}.{$format}.twig";

        $customTemplate = $admin->getController().':'.$template;
        $fallbackTemplate = $admin->getFallbackTemplateDirectory() ?
            $admin->getFallbackTemplateDirectory().':'.$template : null;
        $globalFallbackTemplate = $this->globalFallbackTemplate ? $this->globalFallbackTemplate.':'.$template : null;

        try {
            return $this->twig->loadTemplate($customTemplate);
        } catch (\Twig_Error_Loader $mainError) {
            $nextTemplate = $fallbackTemplate ?: $globalFallbackTemplate;
            $this->logger->notice(
                "Missing template {$customTemplate}, falling back to template {$nextTemplate}",
                [
                    'template' => $customTemplate,
                    'admin' => $admin->getCode(),
                    'action' => $action->getCode(),
                ]
            );
        }

        if ($fallbackTemplate) {
            try {
                return $this->twig->loadTemplate($fallbackTemplate);
            } catch (\Twig_Error_Loader $fallbackError) {
                $this->logger->critical(
                    "Missing template '{$customTemplate}' and fallback template '{$fallbackTemplate}'",
                    [
                        'template' => $customTemplate,
                        'fallbackTemplate' => $fallbackTemplate,
                        'admin' => $admin->getCode(),
                        'action' => $action->getCode(),
                        'error' => $fallbackError,
                    ]
                );
            }
        } else {
            try {
                return $this->twig->loadTemplate($globalFallbackTemplate);
            } catch (\Twig_Error_Loader $fallbackError) {
                $this->logger->critical(
                    "Missing template '{$customTemplate}' and global fallback template '{$globalFallbackTemplate}'",
                    [
                        'template' => $customTemplate,
                        'globalFallbackTemplate' => $globalFallbackTemplate,
                        'admin' => $admin->getCode(),
                        'action' => $action->getCode(),
                        'fallbackError' => $fallbackError,
                    ]
                );
            }
        }

        throw $mainError;
    }
}
