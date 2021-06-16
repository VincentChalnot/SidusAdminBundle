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

namespace Sidus\AdminBundle\Request;

use Sidus\AdminBundle\Admin\Action;

class ActionResponse implements ActionResponseInterface
{
    public function __construct(
        protected Action $action,
        protected array $parameters,
    ) {
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
