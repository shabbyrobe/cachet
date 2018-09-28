<?php
namespace Cachet\Util;

// as at 201612, there is some weirdness around these constants. the latest
// version of APCU doesn't appear to have the appropriate APCU_* constants
// defined, even though the documentation references them.
if (!defined('APCU_ITER_VALUE') && defined('APC_ITER_VALUE')) {
    define('APCU_ITER_VALUE', APC_ITER_VALUE);
}
if (!defined('APCU_ITER_KEY') && defined('APC_ITER_KEY')) {
    define('APCU_ITER_KEY', APC_ITER_KEY);
}
if (!defined('APCU_ITER_ALL') && defined('APC_ITER_ALL')) {
    define('APCU_ITER_ALL', APC_ITER_ALL);
}
if (!defined('APCU_LIST_ACTIVE') && defined('APC_LIST_ACTIVE')) {
    define('APCU_LIST_ACTIVE', APC_LIST_ACTIVE);
}

if (class_exists('APCUIterator')) {
    /**
     * @suppress PhanRedefineClass
     */
    class APCUIterator extends \APCUIterator {}
}
elseif (class_exists('APCIterator')) {
    /**
     * @suppress PhanRedefineClass
     */
    class APCUIterator extends \APCIterator
    { 
        public function __construct(
            $search=null, $format=APCU_ITER_ALL, 
            $chunkSize=100, $list=APCU_LIST_ACTIVE)
        {
            parent::__construct("user", $search, $format, $chunkSize, $list);
        }
    }
}
else {
    throw new \Exception("Could not create Cachet\Util\APCUIterator".
        "class from the available extensions");
}
