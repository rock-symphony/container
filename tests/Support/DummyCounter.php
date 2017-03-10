<?php
namespace RockSymphony\ServiceContainer\Tests\Support;

class DummyCounter
{
    const CLASS_NAME = __CLASS__;
    
    /** @var int */
    public $start;
    /** @var int */
    public $step;
    
    /**
     * @param int $start
     * @param int $step
     */
    public function __construct($start = 0, $step = 1)
    {
        $this->start = $start;
        $this->step = $step;
    }
}
