<?php
namespace RockSymfony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymfony\ServiceContainer\ServiceContainer;

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
        $this->assertFalse($this->container->isBound('test'));
        $this->assertFalse($this->container->isResolved('test'));
        
        $this->container->instance('test', $this);
    
        $this->assertTrue($this->container->isBound('test'));
        $this->assertTrue($this->container->isResolved('test'));
    }
    
    /**
     * @test
     * @depends it_should_set_services
     */
    public function it_should_get_services()
    {
        $this->container->instance('test', $this);
        
        $test = $this->container->resolve('test');
    
        $this->assertInstanceOf(__CLASS__, $test);
        $this->assertSame($this, $test);
    }
    
    /**
     * @test
     */
    public function it_should_fail_if_unknown_binding_requested()
    {
        $this->setExpectedException('RockSymfony\ServiceContainer\Exceptions\BindingResolutionException');
        $this->container->resolve('unicorn');
    }
}