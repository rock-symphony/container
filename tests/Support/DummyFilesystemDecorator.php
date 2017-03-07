<?php
namespace RockSymfony\ServiceContainer\Tests\Support;

class DummyFilesystemDecorator
{
    const CLASS_NAME = __CLASS__;
    
    /** @var DummyCache */
    public $filesystem;
    
    public function __construct(DummyFilesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
