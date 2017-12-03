<?php
namespace RockSymphony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymphony\ServiceContainer\ServiceContainerLayer;
use RockSymphony\ServiceContainer\ServiceContainer;
use RockSymphony\ServiceContainer\Tests\Support\DummyCache;
use RockSymphony\ServiceContainer\Tests\Support\DummyFilesystem;
use RockSymphony\ServiceContainer\Tests\Support\DummyFilesystemDecorator;

class LayerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_inherit_parent_layer_services()
    {
        $parent = new ServiceContainer();
        $parent->set('test_instance', $this);

        $layer = new ServiceContainerLayer($parent);

        $this->assertTrue($layer->has('test_instance'));
        $this->assertSame($this, $layer->get('test_instance'));
    }
    /**
     * @test
     */
    public function it_should_inherit_parent_layer_service_resolvers()
    {
        $parent = new ServiceContainer();
        $parent->bindResolver('test_resolver', function () {
            return $this;
        });

        $layer = new ServiceContainerLayer($parent);

        $this->assertTrue($layer->has('test_resolver'));
        $this->assertSame($this, $layer->get('test_resolver'));
    }

    /**
     * @test
     */
    public function it_should_define_new_services_on_the_lower_layer_only()
    {
        $parent = new ServiceContainer();
        $parent->set('test_instance', $this);

        $layer = new ServiceContainerLayer($parent);

        $this->assertTrue($layer->has('test_instance'));

        $test_override = new \stdClass();

        $layer->set('test_instance', $test_override);

        // "test_instance" is overriden
        $this->assertTrue($layer->has('test_instance'));
        $this->assertSame($test_override, $layer->get('test_instance'));

        // parent "test_instance" is not changed
        $this->assertTrue($parent->has('test_instance'));
        $this->assertSame($this, $parent->get('test_instance'));
    }

    /**
     * @test
     */
    public function it_should_define_new_service_resolvers_on_the_lower_layer()
    {
        $parent = new ServiceContainer();
        $parent->bindResolver('test_resolver', function () {
            return $this;
        });

        $layer = new ServiceContainerLayer($parent);

        $this->assertTrue($layer->has('test_resolver'));

        $test_override = new \stdClass();

        $layer->bindResolver('test_resolver', function () use ($test_override) {
            return $test_override;
        });

        // "test_instance" is overriden
        $this->assertTrue($layer->has('test_resolver'));
        $this->assertSame($test_override, $layer->get('test_resolver'));

        // parent "test_instance" is not changed
        $this->assertTrue($parent->has('test_resolver'));
        $this->assertSame($this, $parent->get('test_resolver'));
    }

    /**
     * @test
     */
    public function it_should_resolve_services_recursively_inheriting_definitions()
    {
        $local_filesystem = new DummyFilesystem('/', 'local');

        $parent = new ServiceContainer();
        $parent->bindResolver(DummyFilesystem::CLASS_NAME, function () use ($local_filesystem) {
            return $local_filesystem;
        });

        $layer = new ServiceContainerLayer($parent);

        // 1) resolve Decorator using parent service container
        /** @var DummyFilesystemDecorator $decorated_filesystem */
        $decorated_filesystem = $parent->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertSame($local_filesystem, $decorated_filesystem->filesystem);

        // 2) resolve Decorator using lower layer service container
        /** @var DummyFilesystemDecorator $layer_decorated_filesystem */
        $layer_decorated_filesystem = $layer->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertNotSame($decorated_filesystem, $layer_decorated_filesystem);
        $this->assertSame($local_filesystem, $layer_decorated_filesystem->filesystem);
    }

    /**
     * @test
     */
    public function it_should_resolve_services_recursively_preferring_lower_level()
    {
        $local_filesystem = new DummyFilesystem('/', 'local');
        $ssh_filesystem = new DummyFilesystem('/mnt/ssh', 'ssh');

        $parent = new ServiceContainer();
        $parent->bindResolver(DummyFilesystem::CLASS_NAME, function () use ($local_filesystem) {
            return $local_filesystem;
        });

        $layer = new ServiceContainerLayer($parent);
        $layer->bindResolver(DummyFilesystem::CLASS_NAME, function () use ($ssh_filesystem) {
            return $ssh_filesystem;
        });

        // 1) resolve Decorator using parent service container
        /** @var DummyFilesystemDecorator $decorated_filesystem */
        $decorated_filesystem = $parent->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertSame($local_filesystem, $decorated_filesystem->filesystem);

        // 2) resolve Decorator using lower layer service container
        /** @var DummyFilesystemDecorator $layer_decorated_filesystem */
        $layer_decorated_filesystem = $layer->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertNotSame($decorated_filesystem, $layer_decorated_filesystem);
        $this->assertSame($ssh_filesystem, $layer_decorated_filesystem->filesystem);
    }

    /**
     * @test
     */
    public function it_should_resolve_inherited_services_recursively_using_inherited_definitions_only()
    {
        $local_filesystem = new DummyFilesystem('/', 'local');
        $ssh_filesystem = new DummyFilesystem('/mnt/ssh', 'ssh');

        $parent = new ServiceContainer();
        $parent->bindResolver(DummyFilesystem::CLASS_NAME, function () use ($local_filesystem) {
            return $local_filesystem;
        });
        // Decorator is now defined on the parent layer
        $parent->bindResolver(DummyFilesystemDecorator::CLASS_NAME, function () use ($parent) {
            return $parent->construct(DummyFilesystemDecorator::CLASS_NAME);
        });

        $layer = new ServiceContainerLayer($parent);
        $layer->bindResolver(DummyFilesystem::CLASS_NAME, function () use ($ssh_filesystem) {
            return $ssh_filesystem;
        });

        // 1) resolve Decorator using parent service container
        /** @var DummyFilesystemDecorator $decorated_filesystem */
        $decorated_filesystem = $parent->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertSame($local_filesystem, $decorated_filesystem->filesystem);

        // 2) resolve Decorator using lower layer service container => it should ignore lower layer Filesystem
        /** @var DummyFilesystemDecorator $layer_decorated_filesystem */
        $layer_decorated_filesystem = $layer->resolve(DummyFilesystemDecorator::CLASS_NAME);
        $this->assertNotSame($decorated_filesystem, $layer_decorated_filesystem);
        $this->assertSame($local_filesystem, $layer_decorated_filesystem->filesystem);
    }
}
