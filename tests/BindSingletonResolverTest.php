<?php
namespace RockSymfony\ServiceContainer\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use RockSymfony\ServiceContainer\ServiceContainer;

/**
 * @see ServiceContainer::bindSingletonResolver()
 */
class BindSingletonResolverTest extends TestCase
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
    public function it_should_resolve_services_bound_to_deferred_resolver_function()
    {
        $this->assertFalse($this->container->has('now'));
        
        $resolved_count = 0;
        
        $this->container->bindSingletonResolver('now', function () use (& $resolved_count) {
            $resolved_count++;
            return new DateTime();
        });
        
        $this->assertTrue($this->container->has('now'));
        $this->assertEquals(0, $resolved_count);
        
        $now = $this->container->resolve('now');
        $another_now = $this->container->resolve('now');
    
        $this->assertEquals(1, $resolved_count);
        
        self::assertSame($now, $another_now);
    }
}
