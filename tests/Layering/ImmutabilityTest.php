<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Tests\Layering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The guiding principle of the bundle: nothing that survives a request in a long-running worker. Every class of the
 * bundle only has readonly properties (the properties inherited from the framework are not ours to judge) and no
 * static property, so no service can keep anything from a request and no object can be altered once built.
 */
final class ImmutabilityTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function classes(): iterable
    {
        $root = \dirname(__DIR__, 2).'/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $class = 'Sidus\AdminBundle\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), \strlen($root) + 1));
            if (class_exists($class)) {
                yield $class => [$class];
            }
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('classes')]
    public function testEveryPropertyIsReadonlyAndNothingIsStatic(string $class): void
    {
        $reflection = new \ReflectionClass($class);
        $mutable = [];
        foreach ($reflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $class) {
                continue;
            }
            if ($property->isStatic()) {
                $mutable[] = '$'.$property->getName().' is static';
            } elseif (!$property->isReadOnly()) {
                $mutable[] = '$'.$property->getName().' is not readonly';
            }
        }

        self::assertSame([], $mutable, $class.' keeps mutable state');
    }

    public function testTheModelIsFinalAndReadonly(): void
    {
        foreach (['Model\Admin', 'Model\Action', 'Model\PermissionCheck', 'Model\ActionLink', 'Registry\AdminRegistry', 'Request\AdminContext'] as $name) {
            $reflection = new \ReflectionClass('Sidus\AdminBundle\\'.$name);
            self::assertTrue($reflection->isFinal() && $reflection->isReadOnly(), $name.' must be final readonly');
        }
    }

    public function testTheSourcesAreFoundByTheTest(): void
    {
        self::assertGreaterThan(30, iterator_count(new \ArrayIterator(iterator_to_array(self::classes()))), 'the class discovery of the test is broken');
    }
}
