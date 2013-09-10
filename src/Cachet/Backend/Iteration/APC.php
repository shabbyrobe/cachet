<?php
namespace Cachet\Backend\Iteration;

class APC extends \IteratorIterator
{
    use ItemKey;
    
	public function __construct($keyRegex, $chunkSize, $mode)
	{
        $this->setMode($mode);
        $innerIterator = new \APCIterator('user', $keyRegex, APC_ITER_VALUE, $chunkSize);
		parent::__construct($innerIterator);
	}
    
	protected function getCurrent()
    {
        $current = parent::current();
        return $current ? $current['value'] : null;
    }
}
