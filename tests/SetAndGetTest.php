<?php
namespace RockSymphony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymphony\ServiceContainer\ServiceContainer;

/**
 * @see ServiceContainer::set()
 * @see ServiceContainer::get()
 * @see ServiceContainer::has()
 */
class SetAndGetTest extends TestCase
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
    public function it_should_set_services()
    {
        $this->assertFalse($this->container->has('test'));

        $this->container->set('test', $this);

        $this->assertTrue($this->container->has('test'));
    }

    /**
     * @test
     * @depends it_should_set_services
     */
    public function it_should_get_services()
    {
        $this->container->set('test', $this);

        $test = $this->container->resolve('test');

        $this->assertInstanceOf(__CLASS__, $test);
        $this->assertSame($this, $test);
    }

    /**
     * @test
     */
    public function it_should_fail_if_unknown_binding_requested()
    {
        $this->setExpectedException('RockSymphony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->resolve('unicorn');
    }
}
