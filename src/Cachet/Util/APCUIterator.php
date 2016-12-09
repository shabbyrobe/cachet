<?php
namespace Cachet\Util;

if (class_exists('APCUIterator')) {
    class APCUIterator extends \APCUIterator {}
}
else {
    class APCUIterator extends \APCIterator
    {
        public function __construct(...$args)
        {
            array_unshift($args, "user");
            parent::__construct(...$args);
        }
    }
}
