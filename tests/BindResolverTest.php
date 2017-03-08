<?php
namespace RockSymfony\ServiceContainer\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use RockSymfony\ServiceContainer\ServiceContainer;

/**
 * @see ServiceContainer::bindResolver()
 */
class BindResolverTest extends TestCase
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
    public function it_should_resolve_services_bound_to_resolver_function()
    {
        $this->assertFalse($this->container->has('now'));
        
        $this->container->bindResolver('now', function () {
            return new DateTime();
        });
    
        $this->assertTrue($this->container->has('now'));
        
        $now = $this->container->resolve('now');
    
        $this->assertTrue($now instanceof DateTime);
    }
    
    /**
     * @test
     */
    public function it_should_call_resolver_function_every_time_you_resolve_a_service()
    {
        $this->assertFalse($this->container->has('now'));
    
        $resolved_count = 0;
    
        $this->container->bindResolver('now', function () use (& $resolved_count) {
            $resolved_count++;
            return new DateTime();
        });
    
        $this->assertTrue($this->container->has('now'));
        $this->assertEquals(0, $resolved_count);
        
        $now = $this->container->resolve('now');
        $another_now = $this->container->resolve('now');
        
        self::assertNotSame($now, $another_now);
        
        $this->assertEquals(2, $resolved_count);
    }
}
