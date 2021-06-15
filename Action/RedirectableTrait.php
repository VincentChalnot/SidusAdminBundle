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

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Admin\Action;

/**
 * Companion trait for RedirectableInterface
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait RedirectableTrait
{
    use ActionInjectableTrait;

    protected Action $redirectAction;

    public function setRedirectAction(Action $action): void
    {
        $this->redirectAction = $action;
    }

    public function setAction(Action $action): void
    {
        $this->action = $action;
        $this->redirectAction = $action;
    }
}
