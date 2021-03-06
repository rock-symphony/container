RockSymphony Service Container
==============================

[![Build Status](https://travis-ci.org/rock-symphony/container.svg?branch=master)](https://travis-ci.org/rock-symphony/container) [![StyleCI](https://styleci.io/repos/84583083/shield?branch=master)](https://styleci.io/repos/84583083)


An indie Service Container implementation based on Laravel Container.

### Philosophy

- Based on Laravel Container (has most of features of [illuminate/container][laravel-container] 5.3)
- [PSR Container][psr-11] compatibility
- [Semantic Versioning](http://semver.org/)
- One dependency (psr/container interface)

### Features

- PHP 5.4+, PHP 7.0+
- Automatic dependencies resolution
- Dependency-injecting constructor calls
- Dependency-injecting method calls

Usage
-----

### Installation

Use [composer](http://getcomposer.org/).

```sh
composer require rock-symphony/container:^2.0
```

### Basics

Of course you can put services to container (`->set()`) and get them from it (`->get()`), 
as well as check if container has specific service (`->has()`).

```php
<?php

use RockSymphony\ServiceContainer\ServiceContainer;

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
/** @var $container \RockSymphony\ServiceContainer\ServiceContainer */
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
/** @var $container \RockSymphony\ServiceContainer\ServiceContainer */
// Definition:
$container->alias('logger', \Psr\Log\LoggerInterface::class);

// Consumer:
$logger = $container->get(\Psr\Log\LoggerInterface::class);
// ... or 
$logger = $container->get('logger'); // 100% equivalent
$logger->info('Nice!');
```



### Binding to a resolver function 

You can declare a service by providing a resolver closure function (`->bindResolver()`).
Service container will call that function every time you resolve service.

```php
<?php
/** @var $container \RockSymphony\ServiceContainer\ServiceContainer */
// Definition:
$container->bindResolver('now', function () {
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

It works similar to `->bindResolver()`, but stores result after first invocation.

```php
<?php
/** @var $container \RockSymphony\ServiceContainer\ServiceContainer */
// Definition:
$container->bindSingletonResolver('cache', function () {
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
use RockSymphony\ServiceContainer\ServiceContainer;

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


### Isolated extension to service container

A use-case: you want to create a new container inheriting services from the existing one. 
But you don't want to re-define the services again, using the originally defined ones.
Also you want to provide more services, without modifying the original container.

Think of it as JavaScript variables scopes: a nested scope inherits all the variables from parent scope.
But defining new scope variables won't modify the parent scope. That's it.

```php
$parent = new ServiceContainer();
$parent->set('configuration', $global_configuration);

$layer = new ServiceContainerLayer($existing_container);
$layer->set('configuration', $layer_configuration); 
$layer->bindResolver('layer_scope_service', ...);
// and so on

var_dump($parent->get('configuration') === $layer->get('configuration')); // "false"
```      


### Automatic dependency injection 

#### Dependency-injecting construction

You can construct any class instance automatically injecting class-hinted dependencies from service container. 
It will try to resolve dependencies from container or construct them recursively resolving their dependencies.

```php
<?php

// Class we need to inject dependencies into
class LoggingCacheDecorator {
    public function __construct(CacheInterface $cache, LoggerInterface $logger, array $options = []) {
        // initialize
    }
}

/** @var $container RockSymphony\ServiceContainer\ServiceContainer */
// Definition:
$container->set(LoggerInterface::class, $logger);
$container->set(CacheInterface::class, $cache);


// Consumer:
$logging_cache = $container->construct(LoggingCacheDecorator::class);
// you can also provide constructor arguments with second parameter:
$logging_cache = $container->construct(LoggingCacheDecorator::class, ['options' => ['level' => 'debug']]);
```


#### Dependency-injecting method call

You can call *any [callable][php-callable]* automatically injecting dependencies from service container.
It's primarily intended, but not limited, to call application HTTP controllers. 

```php
<?php
/** @var $container RockSymphony\ServiceContainer\ServiceContainer */

class MyController {
    public function showPost($url, PostsRepository $posts, TemplateEngine $templates)
    {
        // Warning! Pseudo-code :)
        $post = $posts->findPostByUrl($url);
        return $templates->render('post.html', ['post' => $post]); 
    }
    
    public static function error404(TemplateEngine $templates)
    {
        return $templates->render('404.html');
    }
}
// 1) It can auto-inject dependencies into instance method callables.
//    In this case it will check container for PostsRepository and TemplateEngine bindings.
//    Or try to create those instances automatically.
//    Or throw an exception if both options are not possible.
$container->call([$container, 'showPost'], ['url' => '/hello-world']);

// 2) It can construct class and auto-inject dependencies into method call:
//    Here it will first construct a new instance of MyController (see `->construct()`)
//    And then follows the same logic as the call 1) above.
$container->call('MyController@showPost', ['url' => '/hello-world']); 
// ... or the same: 
$container->call('MyController', ['url' => '/hello-world'], 'showPost');

// 3) It can auto-inject dependencies into static method call: 
$container->call(['MyController', 'error404']);
// ... or the same:
$container->call('MyController::error404');

// 4) It can auto-inject dependencies into closure function calls  
$container->call(function (PostsRepository $repository) {
    $repository->erase();
});

```
 
**Note:** Service container only resolves class-hinted arguments (i.e. arguments explicitly type-hinted to a class).
          You should provide required scalar arguments with second argument.
          It will use default value for options arguments (if you don't specify them). 



FAQ
---

1. Why not use [Laravel Container][laravel-container]?

  > We were using Laravel Container for our project internally. 
  > But it's a bad candidate to link it as library as:
  > 
  > - It doesn't follow SemVer &ndash; BC breaks on every minor version bump 
  > - It has unneeded dependency to flooded [illuminate/contracts][laravel-contracts]
  > - It's designed to be used as part of Laravel Framework, thus it's almost unusable as-a-library
  > - You can use all laravel components only at certain version (i.e. all at 5.3; or all at 5.4; but not mixing)
  > - If you want to move forward you are forced to upgrade to latest PHP version (i.e. container 5.4 requires PHP 7.0)
  > - Bloated public API: 31 public API methods (vs 10 public methods in this library) 
  > - Questionable method naming: what's the difference between `->make()` and `->build()`? 


License
-------

This project is licensed under the terms of the [MIT license][mit-license].


[laravel-container]: https://laravel.com/docs/5.3/container
[laravel-contracts]: https://github.com/illuminate/contracts
[psr-11]: https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
[php-callable]: http://php.net/manual/en/language.types.callable.php
[mit-license]: https://opensource.org/licenses/MIT
