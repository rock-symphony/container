<?php
namespace RockSymfony\ServiceContainer;

use Closure;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use InvalidArgumentException;
use RockSymfony\ServiceContainer\Interfaces\DependencyInjectingServiceContainer;
use RockSymfony\ServiceContainer\Exceptions\BindingNotFoundException;
use RockSymfony\ServiceContainer\Exceptions\BindingResolutionException;

class ServiceContainer implements DependencyInjectingServiceContainer
{
    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected $resolved = [];
    
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];
    
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];
    
    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = [];
    
    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected $extenders = [];
    
    /**
     * The stack of concretions currently being built.
     *
     * @var array
     */
    protected $buildStack = [];
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || $this->isAlias($id);
    }
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws BindingNotFoundException No entry was found for **this** identifier.
     * @throws BindingResolutionException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (! $this->has($id)) {
            throw new BindingNotFoundException("Requested [$id] binding cannot be found.");
        }
        return $this->resolve($id);
    }
    
    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return void
     */
    public function set($abstract, $instance)
    {
        unset($this->aliases[$abstract]);
        
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * Sets an entry resolver closure function.
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
    public function resolver($id, Closure $resolver)
    {
        $this->dropStaleInstances($id);
        
        $this->bindings[$id] = ['resolver' => $resolver, 'shared' => false];
    }
    
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
    public function deferred($id, Closure $resolver)
    {
        $this->dropStaleInstances($id);
        
        $this->bindings[$id] = ['resolver' => $resolver, 'shared' => true];
    }
    
    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string    $abstract
     * @param  \Closure  $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
        } else {
            $this->extenders[$abstract][] = $closure;
        }
    }
    
    /**
     * Makes the same binding/entry be available by another name.
     *
     * @param string $id    Original binding/entry
     * @param string $alias Another name that should also resolve to original entry
     * @return void
     */
    public function alias($id, $alias)
    {
        $this->aliases[$alias] = $id;
    }
    
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if ($this->isCallableWithAtSign($callback) || $defaultMethod) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }
        
        $dependencies = $this->getMethodDependencies($callback, $parameters);
        
        try {
            return call_user_func_array($callback, $dependencies);
        } catch (Exception $e) {
            // wrap extension with ServiceContainer exception
            throw new BindingResolutionException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Determine if the given string is in Class@method syntax.
     *
     * @param  mixed  $callback
     * @return bool
     */
    private function isCallableWithAtSign($callback)
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }
    
    /**
     * Get all dependencies for a given method.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @return array
     */
    private function getMethodDependencies($callback, array $parameters = [])
    {
        $dependencies = [];
        
        foreach ($this->getCallReflector($callback)->getParameters() as $parameter) {
            $this->addDependencyForCallParameter($parameter, $parameters, $dependencies);
        }
        
        return array_merge($dependencies, $parameters);
    }
    
    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     */
    private function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }
        
        if (is_array($callback)) {
            return new ReflectionMethod($callback[0], $callback[1]);
        }
        
        return new ReflectionFunction($callback);
    }
    
    /**
     * Get the dependency for the given call parameter.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @param  array  $dependencies
     * @return void
     */
    private function addDependencyForCallParameter(ReflectionParameter $parameter, array &$parameters, &$dependencies)
    {
        if (array_key_exists($parameter->name, $parameters)) {
            $dependencies[] = $parameters[$parameter->name];
            
            unset($parameters[$parameter->name]);
        } elseif ($parameter->getClass()) {
            $dependencies[] = $this->resolve($parameter->getClass()->name);
            
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        }
    }
    
    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param  string  $target
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    private function callClass($target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);
        
        // If the listener has an @ sign, we will assume it is being used to delimit
        // the class name from the handle method name. This allows for handlers
        // to run multiple handler methods in a single class for convenience.
        $method = count($segments) == 2 ? $segments[1] : $defaultMethod;
        
        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }
        
        return $this->call([$this->resolve($segments[0]), $method], $parameters);
    }
    
    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function resolve($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        
        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
    
        $concrete = $this->getConcrete($abstract);
    
        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($concrete === $abstract) {
            $object = $this->construct($concrete, $parameters);
        
        } elseif ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        
        } else {
            $object = $this->resolve($concrete, $parameters);
        }
        
        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }
        
        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        
        $this->resolved[$abstract] = true;
        
        return $object;
    }
    
    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string $abstract
     * @return string|Closure $concrete
     */
    private function getConcrete($abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (! isset($this->bindings[$abstract])) {
            return $abstract;
        }
        
        return $this->bindings[$abstract]['resolver'];
    }
    
    /**
     * Get the extender callbacks for a given type.
     *
     * @param  string  $abstract
     * @return array
     */
    private function getExtenders($abstract)
    {
        if (isset($this->extenders[$abstract])) {
            return $this->extenders[$abstract];
        }
        
        return [];
    }
    
    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException
     */
    public function construct($concrete, array $parameters = [])
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $exception) {
            // wrap ReflectionException with service container
            throw new BindingResolutionException("Target [$concrete] class cannot be found.", 0, $exception);
        }
        
        
        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (! $reflector->isInstantiable()) {
            if (! empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);
                
                $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }
            
            throw new BindingResolutionException($message);
        }
        
        $this->buildStack[] = $concrete;
        
        $constructor = $reflector->getConstructor();
        
        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            
            return new $concrete;
        }
        
        $dependencies = $constructor->getParameters();
        
        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $parameters = $this->keyParametersByArgument(
          $dependencies, $parameters
        );
        
        $instances = $this->getDependencies(
          $dependencies, $parameters
        );
        
        array_pop($this->buildStack);
        
        return $reflector->newInstanceArgs($instances);
    }
    
    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array  $parameters
     * @param  array  $primitives
     * @return array
     */
    private function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            
            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Resolve a non-class hinted dependency.
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException
     */
    private function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        
        throw new BindingResolutionException($message);
    }
    
    /**
     * Resolve a class based dependency from the container.
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \RockSymfony\ServiceContainer\Exceptions\BindingResolutionException
     */
    private function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->resolve($parameter->getClass()->name);
        }
            
            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            
            throw $e;
        }
    }
    
    /**
     * If extra parameters are passed by numeric ID, rekey them by argument name.
     *
     * @param  array  $dependencies
     * @param  array  $parameters
     * @return array
     */
    private function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);
                
                $parameters[$dependencies[$key]->name] = $value;
            }
        }
        
        return $parameters;
    }
    
    
    /**
     * Determine if a given type is shared.
     *
     * @param  string  $abstract
     * @return bool
     */
    private function isShared($abstract)
    {
        if (isset($this->instances[$abstract])) {
            return true;
        }
        
        if (! isset($this->bindings[$abstract]['shared'])) {
            return false;
        }
        
        return $this->bindings[$abstract]['shared'] === true;
    }
    
    /**
     * Determine if a given string is an alias.
     *
     * @param  string  $name
     * @return bool
     */
    private function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }
    
    /**
     * Get the alias for an abstract if available.
     *
     * @param  string  $abstract
     * @return string
     *
     * @throws \LogicException
     */
    private function getAlias($abstract)
    {
        if (! isset($this->aliases[$abstract])) {
            return $abstract;
        }
        
        if ($this->aliases[$abstract] === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }
        
        return $this->getAlias($this->aliases[$abstract]);
    }
    
    /**
     * Drop all of the stale instances and aliases.
     *
     * @param  string  $abstract
     * @return void
     */
    private function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }
}
