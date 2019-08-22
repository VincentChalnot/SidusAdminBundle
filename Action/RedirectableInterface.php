<?php declare(strict_types=1);
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Admin\Action;

/**
 * Allow the action to redirect to a custom action
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface RedirectableInterface extends ActionInjectableInterface
{
    /**
     * @param Action $action
     */
    public function setRedirectAction(Action $action): void;
}
