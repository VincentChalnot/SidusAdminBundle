<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Attribute;

/**
 * Use this attribute to enable the argument resolver to work with generic objects. You don't need to use this attribute
 * if the argument is hard-typed with the entity class.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AdminEntity
{
}
