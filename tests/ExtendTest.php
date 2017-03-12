<?php

namespace RockSymphony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymphony\ServiceContainer\ServiceContainer;
use RockSymphony\ServiceContainer\Tests\Support\DummyFilesystem;
use RockSymphony\ServiceContainer\Tests\Support\DummyFilesystemDecorator;

/**
 * @see ServiceContainer::extend()
 */
class ExtendTest extends TestCase
{
    /** @var ServiceContainer */
    private $container;

    protected function setUp()
    {
        $this->container = new ServiceContainer();
    }

    /**
     * @test
     */
    public function it_should_extend_service_instances()
    {
        $filesystem = new DummyFilesystem('/tmp');

        $this->assertFalse($this->container->has('fs'), 'Container should not have "fs" service initially');

        $this->container->set('fs', $filesystem);

        $this->assertTrue($this->container->has('fs'), 'Container should have "fs" service now');

        $fs = $this->container->get('fs');

        $this->assertTrue($fs instanceof DummyFilesystem, '"fs" service should be an instance of DummyFilesystem');
        $this->assertSame($fs, $this->container->get('fs'), '"fs" service should be shared');

        $this->container->extend('fs', function (DummyFilesystem $fs) {
            return new DummyFilesystemDecorator($fs);
        });

        $this->assertTrue($this->container->has('fs'), 'Container should still have "fs" service');

        $next_fs = $this->container->get('fs');

        $this->assertTrue(
            $next_fs instanceof DummyFilesystemDecorator,
            '"fs" service should be an instance of DummyFilesystemDecorator'
        );
        $this->assertSame(
            $next_fs,
            $this->container->get('fs'),
            '"fs" service should still be shared'
        );
    }

    /**
     * @test
     */
    public function it_should_extend_deferred_bindings_when_they_are_resolved()
    {
        $this->assertFalse($this->container->has('fs'), 'Container should not have "fs" service initially');

        $resolved_counter = 0;

        $this->container->bindResolver('fs', function () use (&$resolved_counter) {
            $resolved_counter++;

            return new DummyFilesystem('/tmp');
        });

        $this->assertTrue($this->container->has('fs'), 'Container should have "fs" bound service now');
        $this->assertEquals(0, $resolved_counter, 'Container should not resolve "fs" yet');

        $extend_counter = 0;

        $this->container->extend('fs', function (DummyFilesystem $fs) use (&$extend_counter) {
            $extend_counter++;

            return new DummyFilesystemDecorator($fs);
        });

        $this->assertTrue($this->container->has('fs'), 'Container should still have "fs" service');
        $this->assertEquals(0, $resolved_counter, 'Container should not resolve "fs" yet');

        $fs = $this->container->get('fs');

        $this->assertTrue(
            $fs instanceof DummyFilesystemDecorator,
            '"fs" service should be an instance of DummyFilesystemDecorator'
        );
        $this->assertNotSame(
            $fs,
            $this->container->get('fs'),
            '"fs" service should not be shared'
        );

        $this->assertEquals(2, $resolved_counter, 'Original "fs" binding should be resolved twice');
        $this->assertEquals(2, $extend_counter, '"fs" binding extension should be resolved twice');

        $this->container->get('fs');

        $this->assertEquals(3, $resolved_counter, 'Original "fs" binding should be resolved tripple');
        $this->assertEquals(3, $extend_counter, '"fs" binding extension should be resolved tripple');
    }

    /**
     * @test
     */
    public function it_should_extend_deferred_shared_bindings_when_they_are_resolved()
    {
        $this->assertFalse($this->container->has('fs'), 'Container should not have "fs" service initially');

        $resolved_counter = 0;

        $this->container->bindSingletonResolver('fs', function () use (&$resolved_counter) {
            $resolved_counter++;

            return new DummyFilesystem('/tmp');
        });

        $this->assertTrue($this->container->has('fs'), 'Container should have "fs" bound service now');
        $this->assertEquals(0, $resolved_counter, 'Container should not resolve "fs" yet');

        $extend_counter = 0;

        $this->container->extend('fs', function (DummyFilesystem $fs) use (&$extend_counter) {
            $extend_counter++;

            return new DummyFilesystemDecorator($fs);
        });

        $this->assertTrue($this->container->has('fs'), 'Container should still have "fs" service');
        $this->assertEquals(0, $resolved_counter, 'Container should not resolve "fs" yet');

        $fs = $this->container->get('fs');

        $this->assertTrue(
            $fs instanceof DummyFilesystemDecorator,
            '"fs" service should be an instance of DummyFilesystemDecorator'
        );
        $this->assertSame(
            $fs,
            $this->container->get('fs'),
            '"fs" service should be shared'
        );

        $this->assertEquals(1, $resolved_counter, 'Original "fs" binding should be resolved now');
        $this->assertEquals(1, $extend_counter, '"fs" binding extension should be resolved now');

        $this->container->get('fs');

        $this->assertEquals(1, $resolved_counter, 'Original "fs" binding should be resolved just once');
        $this->assertEquals(1, $extend_counter, '"fs" binding extension should be resolved just once');
    }
}
