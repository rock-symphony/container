<?php
namespace RockSymphony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymphony\ServiceContainer\ServiceContainer;
use RockSymphony\ServiceContainer\Tests\Support\DummyCache;
use RockSymphony\ServiceContainer\Tests\Support\DummyCounter;
use RockSymphony\ServiceContainer\Tests\Support\DummyFilesystem;

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
        $counter = $this->container->construct(DummyCounter::CLASS_NAME, array('step' => 25));
        $this->assertTrue($counter instanceof DummyCounter);
        $this->assertEquals(0, $counter->start);
        $this->assertEquals(25, $counter->step);

        /** @var DummyCounter $counter2 */
        $counter2 = $this->container->construct(DummyCounter::CLASS_NAME, array('start' => 10));
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
        $counter = $this->container->construct(DummyCounter::CLASS_NAME, array(/* start = */ 5, /* step = */ 10));
        $this->assertTrue($counter instanceof DummyCounter);
        $this->assertEquals(5, $counter->start);
        $this->assertEquals(10, $counter->step);
    }

    /**
     * @test
     */
    public function it_should_fail_if_a_required_not_hinted_parameter_is_not_specified()
    {
        $this->setExpectedException('RockSymphony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->construct(DummyFilesystem::CLASS_NAME);
    }

    /**
     * @test
     */
    public function it_should_recursively_resolve_dependencies_and_fail_if_it_is_not_possible()
    {
        $this->setExpectedException('RockSymphony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->construct(DummyCache::CLASS_NAME);
    }

    /**
     * @test
     */
    public function it_should_recursively_resolve_dependencies()
    {
        $is_bind_resolution_called = false;
        $this->container->bindResolver(DummyFilesystem::CLASS_NAME, function () use (&$is_bind_resolution_called) {
            $is_bind_resolution_called = true;

            return new DummyFilesystem('/');
        });

        /** @var DummyCache $cache */
        $cache = $this->container->construct(DummyCache::CLASS_NAME, array('options' => array('ttl' => '1 year')));
        $this->assertTrue($cache instanceof DummyCache);
        $this->assertTrue($cache->filesystem instanceof DummyFilesystem);
        $this->assertTrue($is_bind_resolution_called);
        $this->assertEquals(array('ttl' => '1 year'), $cache->options);
    }
}
