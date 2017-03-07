RockSymfony Service Container
=============================

An indie Service Container implementation based on Laravel Container.

### Philosophy

- Based on Laravel Container (has most of features of [illuminate/container](laravel-container) 5.3)
- [PSR Container](psr-11) compatibility
- [Semantic Versioning](http://semver.org/)
- One dependency (psr/container interface)

### Features

- Automatic dependencies resolution
- Dependency-resolving constructor calls
- Dependency-resolving method calls

Usage
-----

### Basics

Of course you can put services to container (`->set()`) and get them from it (`->get()`), 
as well as check if container has specific service (`->has()`).

```php
<?php

use RockSymfony\ServiceContainer\ServiceContainer;

$container = new ServiceContainer();

// Definition:
// Set a service instance to container
$container->set('acme', new AcmeService());

// Consumer:
// Check if there is a service binding for given service ID  
echo $container->has('acme') ? 'It has acme service' : 'wtf?';

// Get a service from container
$acme = $container->get('acme');
$acme->doSomeStuff();
```



### Using abstract interfaces

It's handy to bind services by their abstract interfaces 
to explicitly declare it's interface on both definition and consumer sides.
 
```php
<?php
/** @var $container \RockSymfony\ServiceContainer\ServiceContainer */
// Definition:
// Note we bind instance by it's **abstract** interface.
// This way you force consumers to not care about implementation details, but rely on interface. 
$container->set(\Psr\Log\LoggerInterface::class, $my_fancy_psr_logger_implementation);

// Consumer:
// Then you have a consumer that needs a logger implementation,
// but doesn't care on details. It can use any PSR-compatible logger.
$logger = $container->get(\Psr\Log\LoggerInterface::class);
$logger->info('Nice!');
```



### Aliases

Sometimes you may also want to bind the same service by different IDs.
You can use aliases for that (`->alias()`):

```php
<?php
/** @var $container \RockSymfony\ServiceContainer\ServiceContainer */
// Definition:
$container->alias('logger', \Psr\Log\LoggerInterface::class);

// Consumer:
$logger = $container->get(\Psr\Log\LoggerInterface::class);
// ... or 
$logger = $container->get('logger'); // 100% equivalent
$logger->info('Nice!');
```



### Binding to a resolver function 

You can declare a service by providing a resolver closure function (`->resolver()`).
Service container will call that function every time you resolve service.

```php
<?php
/** @var $container \RockSymfony\ServiceContainer\ServiceContainer */
// Definition:
$container->resolver('now', function () {
    return new DateTime();
});

// Consumer:
$now = $container->get('now'); // DateTime object
$another_now = $container->get('now'); // another DateTime object

echo $now === $another_now ? 'true' : 'false'; // == false
```



### Deferred resolution service binding  

You can defer service initialization until it is requested for the first time.
A resolver function will be called just once and its result will be stored to service container.

It works similar to `->bind()`, but stores result after first invocation.

```php
<?php
/** @var $container \RockSymfony\ServiceContainer\ServiceContainer */
// Definition:
$container->deferred('cache', function () {
    return new MemcacheCache('127.0.0.1');
});

// Consumer:
$cache = $container->get('cache'); // DateTime object
// do something with $cache
```



### Extending a bound service 

You can extend/decorate an existing service binding with `->extend()` method.

```php
<?php
use RockSymfony\ServiceContainer\ServiceContainer;

/** @var $container ServiceContainer */
// Definition:
$container->deferred('cache', function () {
    return new MemcacheCache('127.0.0.1');
}); 

// Wrap cache service with logging decorator
$container->extend('cache', function($cache, ServiceContainer $container) { 
    // Note: it's passing a service container instance as second parameter
    //       so you can get dependencies from it.
    return new LoggingCacheDecorator($cache, $container->get('logger'));
});

// Consumer:
$cache = $container->get('cache'); // DateTime object
// Uses cache seamlessly as before
// (implying that MemcacheCache and LoggingCacheDecorator have the same interface)
```


FAQ
---

1. Why not use [Laravel Container](laravel-container)?
  > We were using Laravel Container for our project internally. 
  > But it's a bad candidate to link it as library as:
  > 
  > - It doesn't follow SemVer &ndash; BC breaks on every minor version bump 
  > - It has unneeded dependency to flooded (illuminate/contracts)[laravel-contracts]
  > - It's designed to be used as part of Laravel Framework, thus it's almost unusable as-a-library
  > - You can use all laravel components only at certain version (i.e. all at 5.3; or all at 5.4; but not mixing)
  > - If you want to move forward you are forced to upgrade to latest PHP version (i.e. container 5.4 requires PHP 7.0)
  > - Bloated public API: 31 public API methods (vs 10 public methods in this library) 
  > - Questionable method naming: what's the difference between `->make()` and `->build()`? 

[laravel-container]: https://laravel.com/docs/5.3/container
[laravel-contracts]: https://github.com/illuminate/contracts
[psr-11]: https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
