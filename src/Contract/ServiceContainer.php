<?php
namespace RockSymfony\ServiceContainer\Contract;

use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * RockSymfony Container public API interface (for clear big picture on functionality)
 */
interface ServiceContainer extends PsrContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingNotFoundException No entry was found for this identifier.
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id);
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundException`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id);
    
    /**
     * Sets an entry of the container by its identifier.
     *
     * @param string $id       Identifier of the entry to look for.
     * @param mixed  $instance Entry
     */
    public function set($id, $instance);
    
    /**
     * Sets an entry resolver closure function.
     *
     * If $shared is true, resolution result will be stored for all future gets/resolutions.
     *
     * @param string  $id       Service identifier or FQCN
     * @param Closure $resolver Resolver closure function which result will be used as resolved instance
     * @param bool    $shared   Reuse resolution result for future requests of same $id
     * @return void
     */
    public function bind($id, Closure $resolver, $shared = false);
    
    /**
     * Makes the same binding/entry be available by another name.
     *
     * @param string $id    Original binding/entry
     * @param string $alias Another name that should also resolve to original entry
     * @return void
     */
    public function alias($id, $alias);
    
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
