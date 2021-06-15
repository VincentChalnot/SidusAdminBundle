<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Templating;

use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Sidus\AdminBundle\Admin\Action;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\TemplateWrapper;
use function count;

/**
 * Resolve templates based on admin configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class TemplateResolver implements TemplateResolverInterface
{
    public function __construct(
        protected Environment $twig,
        protected LoggerInterface $logger
    ) {
    }

    public function getTemplate(Action $action, string $templateType = 'html'): TemplateWrapper
    {
        $admin = $action->getAdmin();
        if ($action->getTemplate()) {
            // If the template was specified, use this one
            return $this->twig->load($action->getTemplate());
        }

        // Priority to new template_pattern system:
        if (count($admin->getTemplatePattern()) === 0) {
            throw new LogicException("No template configured for action {$admin->getCode()}.{$action->getCode()}");
        }

        foreach ($admin->getTemplatePattern() as $templatePattern) {
            $template = strtr(
                $templatePattern,
                [
                    '{{admin}}' => lcfirst($admin->getCode()),
                    '{{Admin}}' => ucfirst($admin->getCode()),
                    '{{action}}' => lcfirst($action->getCode()),
                    '{{Action}}' => ucfirst($action->getCode()),
                    '{{format}}' => $templateType,
                ]
            );
            try {
                return $this->twig->load($template);
            } catch (LoaderError $mainError) {
                $this->logger->debug("Unable to load template '{$template}': {$mainError->getMessage()}");
                continue;
            }
        }

        $flattened = implode(', ', $admin->getTemplatePattern());
        throw new RuntimeException(
            "Unable to resolve any valid template for the template_pattern configuration: {$flattened}"
        );
    }
}
