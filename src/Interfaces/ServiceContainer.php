<?php
namespace RockSymfony\ServiceContainer\Interfaces;

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
     * Binds an entry resolver closure function.
     *
     * The closure function will be called every
     * time you resolve then given service ID.
     * Its result will be returned as resolved service instance.
     *
     * @see deferred()
     *
     * @param string  $id       Service identifier or FQCN
     * @param Closure $resolver Resolver closure function which result will be used as resolved instance
     * @return void
     */
    public function resolver($id, Closure $resolver);
    
    /**
     * Sets an deferred service resolution function.
     *
     * The closure function will be called just once.
     * Its result will be stored inside service container
     * and returned for all future resolutions of the service ID.
     *
     * Works similar as `->resolver()`, but stores result for future resolutions.
     * @see resolver()
     *
     * @param string  $id       Service identifier or FQCN
     * @param Closure $resolver Resolver closure function which result will be used as resolved instance
     * @return void
     */
    public function deferred($id, Closure $resolver);
    
    /**
     * Makes the same binding/entry be available by another name.
     *
     * @param string $id    Original binding/entry
     * @param string $alias Another name that should also resolve to original entry
     * @return void
     */
    public function alias($id, $alias);
}
