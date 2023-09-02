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

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Model\Action;

/**
 * Companion trait for ActionInjectableInterface
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait ActionInjectableTrait
{
    protected Action $action;

    public function setAction(Action $action): void
    {
        $this->action = $action;
    }
}
