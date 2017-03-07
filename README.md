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
  > - Bloated public API: 31 public API methods (vs 9 public methods in this library) 
  > - Questionable method naming: what's the difference between `->make()` and `->build()`? 

[laravel-container]: https://laravel.com/docs/5.3/container
[laravel-contracts]: https://github.com/illuminate/contracts
[psr-11]: https://github.com/container-interop/fig-standards/blob/master/proposed/container.md
