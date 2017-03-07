<?php
namespace RockSymfony\ServiceContainer\Interfaces;

interface DependencyInjectingServiceContainer extends ServiceContainer
{
    /**
     * Creates an instance of any class resolving dependencies recursively.
     *
     * @param string $class
     * @param array  $parameters
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException Error while resolving dependencies.
     *
     * @return mixed
     */
    public function construct($class, array $parameters = []);
    
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string $callback
     * @param  array $parameters
     * @param  string|null $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);
    
    /**
     * Resolves an abstract dependency from container or instantiate a new instance of given class.
     *
     * @param string $abstract
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingNotFoundException   Error while resolving dependencies.
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException Error while resolving dependencies.
     *
     * @return mixed Resolved concrete implementation of an abstract or a bound named service
     */
    public function resolve($abstract);
}
