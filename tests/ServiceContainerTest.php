<?php
namespace RockSymfony\ServiceContainer\Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use RockSymfony\ServiceContainer\ServiceContainer;

class ServiceContainerTest extends TestCase
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
    public function it_should_bind_and_resolve_services()
    {
        $this->assertFalse($this->container->isBound('now'));
        $this->assertFalse($this->container->isResolved('now'));
        
        $this->container->bind('now', function () {
            return new DateTime();
        });
    
        $this->assertTrue($this->container->isBound('now'));
        $this->assertFalse($this->container->isResolved('now'));
        
        $now = $this->container->resolve('now');
    
        $this->assertTrue($this->container->isBound('now'));
        $this->assertTrue($this->container->isResolved('now'));
        
        $this->assertTrue($now instanceof DateTime);
    }
    
    /**
     * @test
     */
    public function it_should_not_share_services_by_default()
    {
        $this->assertFalse($this->container->isBound('now'));
        
        $this->container->bind('now', function () {
            return new DateTime();
        });
        
        $now = $this->container->resolve('now');
        $another_now = $this->container->resolve('now');
        
        self::assertNotSame($now, $another_now);
    }
    
    /**
     * @test
     */
    public function it_should_share_services_if_asked()
    {
        $this->assertFalse($this->container->isBound('now'));
        
        $this->container->bind('now', function () {
            return new DateTime();
        }, $shared = true);
        
        $now = $this->container->resolve('now');
        $another_now = $this->container->resolve('now');
        
        self::assertSame($now, $another_now);
    }
}
