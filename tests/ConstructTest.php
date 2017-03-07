<?php
namespace RockSymfony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymfony\ServiceContainer\ServiceContainer;
use RockSymfony\ServiceContainer\Tests\Support\DummyCache;
use RockSymfony\ServiceContainer\Tests\Support\DummyCounter;
use RockSymfony\ServiceContainer\Tests\Support\DummyFilesystem;

/**
 * @see ServiceContainer::construct()
 */
class ConstructTest extends TestCase
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
    public function it_should_instantiate_new_instances()
    {
        $stdClass = $this->container->construct('stdClass');
        $this->assertTrue($stdClass instanceof \stdClass);
    }
    
    /**
     * @test
     */
    public function it_should_instantiate_new_instances_using_in_default_constructor_parameters()
    {
        /** @var DummyCounter $counter */
        $counter = $this->container->construct(DummyCounter::CLASS_NAME);
        
        $this->assertTrue($counter instanceof DummyCounter);
        $this->assertEquals(0, $counter->start);
        $this->assertEquals(1, $counter->step);
    }
    
    /**
     * @test
     */
    public function it_should_instantiate_new_instances_with_a_subset_of_parameters_passed()
    {
        /** @var DummyCounter $counter */
        $counter = $this->container->construct(DummyCounter::CLASS_NAME, ['step' => 25]);
        $this->assertTrue($counter instanceof DummyCounter);
        $this->assertEquals(0, $counter->start);
        $this->assertEquals(25, $counter->step);
    
        /** @var DummyCounter $counter2 */
        $counter2 = $this->container->construct(DummyCounter::CLASS_NAME, ['start' => 10]);
        $this->assertTrue($counter2 instanceof DummyCounter);
        $this->assertEquals(10, $counter2->start);
        $this->assertEquals(1, $counter2->step);
        
        $this->assertNotSame($counter, $counter2);
    }
    
    /**
     * @test
     */
    public function it_should_instantiate_new_instances_with_numeric_array()
    {
        /** @var DummyCounter $counter */
        $counter = $this->container->construct(DummyCounter::CLASS_NAME, [ /* start = */ 5, /* step = */ 10]);
        $this->assertTrue($counter instanceof DummyCounter);
        $this->assertEquals(5, $counter->start);
        $this->assertEquals(10, $counter->step);
    }
    
    /**
     * @test
     */
    public function it_should_fail_if_a_required_not_hinted_parameter_is_not_specified()
    {
        $this->expectException('RockSymfony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->construct(DummyFilesystem::CLASS_NAME);
    }
    
    /**
     * @test
     */
    public function it_should_recursively_resolve_dependencies_and_fail_if_it_is_not_possible()
    {
        $this->expectException('RockSymfony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->construct(DummyCache::CLASS_NAME);
    }
    
    /**
     * @test
     */
    public function it_should_recursively_resolve_dependencies()
    {
        $is_bind_resolution_called = false;
        $this->container->resolver(DummyFilesystem::CLASS_NAME, function () use (& $is_bind_resolution_called) {
            $is_bind_resolution_called = true;
            return new DummyFilesystem('/');
        });
        
        /** @var DummyCache $cache */
        $cache = $this->container->construct(DummyCache::CLASS_NAME, ['options' => ['ttl' => '1 year']]);
        $this->assertTrue($cache instanceof DummyCache);
        $this->assertTrue($cache->filesystem instanceof DummyFilesystem);
        $this->assertTrue($is_bind_resolution_called);
        $this->assertEquals(['ttl' => '1 year'], $cache->options);
    }
}
