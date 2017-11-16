<?php
namespace RockSymphony\ServiceContainer\Tests;

use PHPUnit\Framework\TestCase;
use RockSymphony\ServiceContainer\ServiceContainer;

/**
 * @see ServiceContainer::construct()
 */
class CallTest extends TestCase
{
    const CLASS_NAME = __CLASS__;

    /** @var ServiceContainer */
    private $container;

    protected function setUp()
    {
        $this->container = new ServiceContainer();
    }

    public function construct_and_perform_assertions(CallTest $test, $challenge)
    {
        $this->assertNotSame($this, $test);

        return $challenge + 1; // to make sure the method is called
    }

    public function inject_and_perform_assertions(CallTest $test, $challenge)
    {
        $this->assertSame($this, $test);

        return $challenge + 1; // to make sure the method is called
    }

    public static function inject_and_perform_assertions_statically(CallTest $test, $challenge)
    {
        $test->assertInstanceOf(self::CLASS_NAME, $test);

        return $challenge + 1; // to make sure the method is called
    }

    /**
     * @test
     */
    public function it_should_call_instance_methods()
    {
        $this->container->set(self::CLASS_NAME, $this);

        $result = $this->container->call([$this, 'inject_and_perform_assertions'], ['challenge' => 1]);

        $this->assertEquals(2, $result);
    }

    /**
     * @test
     */
    public function it_should_call_static_methods()
    {
        $this->container->set(self::CLASS_NAME, $this);

        $result = $this->container->call([self::CLASS_NAME, 'inject_and_perform_assertions_statically'], ['challenge' => 2]);

        $this->assertEquals(3, $result);

        $result = $this->container->call(self::CLASS_NAME . '::inject_and_perform_assertions_statically', ['challenge' => 3]);

        $this->assertEquals(4, $result);
    }

    /**
     * @test
     */
    public function it_should_throw_exception_if_required_parameter_with_no_hint_is_not_specified()
    {
        $this->container->set(self::CLASS_NAME, $this);

        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            $this->setExpectedException('ArgumentCountError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }

        $this->container->call([$this, 'inject_and_perform_assertions']);
    }

    /**
     * @test
     */
    public function it_should_automatically_create_missing_class_dependencies()
    {
        $result = $this->container->call(self::CLASS_NAME . '@construct_and_perform_assertions', ['challenge' => 5]);

        $this->assertEquals(6, $result);
    }

    /**
     * @test
     */
    public function it_should_call_bound_services_methods_with_at_sign_notation()
    {
        $this->container->set(self::CLASS_NAME, $this);

        $result = $this->container->call(self::CLASS_NAME . '@inject_and_perform_assertions', ['challenge' => 7]);

        $this->assertEquals(8, $result);
    }

    /**
     * @test
     */
    public function it_should_construct_unbound_instances_and_call_methods_with_at_sign_notation()
    {
        $result = $this->container->call(self::CLASS_NAME . '@construct_and_perform_assertions', ['challenge' => 7]);

        $this->assertEquals(8, $result);
    }

    /**
     * @test
     */
    public function it_should_inject_dependencies_into_closures()
    {
        $this->container->set(self::CLASS_NAME, $this);

        $result = $this->container->call(function (CallTest $test, $challenge) {
            $this->assertSame($this, $test);

            return $challenge + 1;
        }, ['challenge' => 9]);

        $this->assertEquals(10, $result);
    }
}
