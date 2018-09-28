<?php
namespace Cachet\Exceptions;

class UnexpectedData extends \RuntimeException
{
    /** @var mixed */
    public $data;

    /**
     * @param string $msg
     * @param mixed $data
     * @param \Exception|null $inner
     */
    function __construct($msg, $data, \Exception $inner=null) {
        parent::__construct($msg, 0, $inner);
        $this->data = $data;
    }
}
