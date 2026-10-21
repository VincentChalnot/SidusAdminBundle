<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Templating;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Sidus\AdminBundle\Model\Action;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\TemplateWrapper;

/**
 * Resolve templates based on admin configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
readonly class TemplateResolver implements TemplateResolverInterface
{
    public function __construct(
        protected Environment $twig,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    public function getTemplate(Action $action, string $templateType = 'html'): TemplateWrapper
    {
        $admin = $action->getAdmin();
        if ($action->getTemplate()) {
            // If the template was specified, use this one
            return $this->twig->load($action->getTemplate());
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
                $this->logger?->debug("Unable to load template '{$template}': {$mainError->getMessage()}");
                continue;
            }
        }

        $flattened = implode(', ', $admin->getTemplatePattern());
        throw new RuntimeException(
            "Unable to resolve any valid template for the template_pattern configuration: {$flattened}"
        );
    }
}
