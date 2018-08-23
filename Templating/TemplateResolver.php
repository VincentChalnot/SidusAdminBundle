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
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;

/**
 * Resolve templates based on admin configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class TemplateResolver
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $globalFallbackTemplate;

    /**
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param \Twig_Environment         $twig
     * @param string                    $globalFallbackTemplate
     * @param LoggerInterface           $logger
     */
    public function __construct(
        AdminConfigurationHandler $adminConfigurationHandler,
        \Twig_Environment $twig,
        $globalFallbackTemplate,
        LoggerInterface $logger
    ) {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->twig = $twig;
        $this->globalFallbackTemplate = $globalFallbackTemplate;
        $this->logger = $logger;
    }


    /**
     * @param Admin  $admin
     * @param Action $action
     * @param string $templateType
     *
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Twig_Error_Loader
     * @throws \UnexpectedValueException
     *
     * @return \Twig_Template
     */
    public function getTemplate(Admin $admin = null, Action $action = null, $templateType = 'html')
    {
        if (!$admin) {
            $admin = $this->adminConfigurationHandler->getCurrentAdmin();
        }
        if (!$action) {
            $action = $admin->getCurrentAction();
        }

        if ($action->getTemplate()) {
            // If the template was specified, do not try to fallback
            return $this->twig->loadTemplate($action->getTemplate());
        }

        $template = ":{$action->getCode()}.{$templateType}.twig";

        $customTemplate = $admin->getController().$template;
        $fallbackTemplate = $admin->getFallbackTemplateDirectory() ?
            $admin->getFallbackTemplateDirectory().$template : null;
        $globalFallbackTemplate = $this->globalFallbackTemplate ? $this->globalFallbackTemplate.$template : null;

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
