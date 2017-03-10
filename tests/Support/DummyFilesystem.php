<?php
namespace RockSymphony\ServiceContainer\Tests\Support;

class DummyFilesystem
{
    const CLASS_NAME = __CLASS__;
    
    /** @var string */
    public $root;
    /** @var array */
    public $options;
    /** @var string */
    public $type;
    
    /**
     * @param string $root
     * @param string $type
     * @param array  $options
     */
    public function __construct($root, $type = 'local', $options = [])
    {
        $this->root = $root;
        $this->type = $type;
        $this->options = $options;
    }
}
