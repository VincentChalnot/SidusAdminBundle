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

use Sidus\AdminBundle\Model\Action;
use Twig\TemplateWrapper;

/**
 * Services implementing this interface must be able to resolve a template based on an action configuration
 */
interface TemplateResolverInterface
{
    public function getTemplate(Action $action, string $templateType = 'html'): TemplateWrapper;
}
