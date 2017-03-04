<?php
namespace RockSymfony\ServiceContainer\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class BindingNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
