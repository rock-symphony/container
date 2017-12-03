<?php
namespace RockSymphony\ServiceContainer;

use RockSymphony\ServiceContainer\Interfaces\ServiceContainerInterface;

/**
 * ServiceContainerLayer allows to construct an extension to an existing Service Container.
 * The new instance will inherit parent service container definitions (at call-time).
 * All the new added or modified services will modify the extension container only,
 * keeping the original one untouched.
 */
class ServiceContainerLayer extends ServiceContainer implements ServiceContainerInterface
{
    /** @var \RockSymphony\ServiceContainer\Interfaces\ServiceContainerInterface */
    private $parentLayer;

    public function __construct(ServiceContainerInterface $parentLayer)
    {
        $this->parentLayer = $parentLayer;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return parent::has($id) || $this->parentLayer->has($id);
    }

    /**
     * @param string $id
     *
     * @throws \RockSymphony\ServiceContainer\Exceptions\BindingNotFoundException
     * @throws \RockSymphony\ServiceContainer\Exceptions\BindingResolutionException
     *
     * @return mixed
     */
    public function get($id)
    {
        // If it's bound to THIS layer, resolve it.
        // This layer takes priority.
        if (parent::has($id)) {
            return parent::get($id);
        }

        // If it's bound to PARENT layer, resolve it there.
        // Getting a known service from parent level is better
        // than re-resolving it here.
        if ($this->parentLayer->has($id)) {
            return $this->parentLayer->get($id);
        }

        // Otherwise, let the `get()` fail on getting an unknown layer
        return parent::get($id);
    }

    /**
     * @param string $abstract
     *
     * @throws \RockSymphony\ServiceContainer\Exceptions\BindingNotFoundException
     * @throws \RockSymphony\ServiceContainer\Exceptions\BindingResolutionException
     *
     * @return mixed
     */
    public function resolve($abstract)
    {
        // If it's bound to THIS layer, resolve it.
        // This layer takes priority.
        if (parent::has($abstract)) {
            return parent::resolve($abstract);
        }

        // If it's bound to PARENT layer, resolve it there.
        // Getting a known service from parent level is better
        // than re-resolving it here.
        if ($this->parentLayer->has($abstract)) {
            return $this->parentLayer->resolve($abstract);
        }

        // Otherwise, resolve the service from scratch here.
        return parent::resolve($abstract);
    }
}
