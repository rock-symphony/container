<?php
namespace RockSymfony\ServiceContainer\Tests\Support;

class DummyCache
{
    const CLASS_NAME = __CLASS__;
    
    /** @var DummyFilesystem */
    public $filesystem;
    /** @var array */
    public $options;
    
    /**
     * @param DummyFilesystem $filesystem
     * @param array           $options
     */
    public function __construct(DummyFilesystem $filesystem, array $options)
    {
        $this->filesystem = $filesystem;
        $this->options = $options;
    }
}
