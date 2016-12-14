<?php
namespace Cachet;

class Item
{
    const COMPACT_CACHE_ID = 0;
    const COMPACT_KEY = 1;
    const COMPACT_VALUE = 2;
    const COMPACT_TIMESTAMP = 3;
    const COMPACT_DEPENDENCY = 4;

    /** @var string */ public $cacheId;
    /** @var string */ public $key;
    /** @var mixed */  public $value;
    /** @var Dependency|null */ public $dependency;
    /** @var int */ public $timestamp;

    /**
     * @param string $cacheId
     * @param string $key
     * @param mixed $value
     */
    public function __construct(
        $cacheId, $key, $value,
        Dependency $dependency=null,
        $timestamp=null
    ) {
        $this->cacheId = $cacheId;
        $this->key = $key;
        $this->value = $value;
        $this->timestamp = $timestamp ?: time();
        $this->dependency = $dependency;
    }

    public function compact()
    {
        $compact = array(
            self::COMPACT_CACHE_ID=>$this->cacheId,
            self::COMPACT_KEY=>$this->key,
            self::COMPACT_VALUE=>$this->value,
            self::COMPACT_TIMESTAMP=>$this->timestamp
        );

        if ($this->dependency) {
            $compact[self::COMPACT_DEPENDENCY] = $this->dependency;
        }

        return $compact;
    }

    /**
     * Returns null if the compacted value is not an array with the correct number of
     * elements.
     * @param array $compacted
     * @throws UnexpectedData
     */
    public static function uncompact($compacted)
    {
        if (!is_array($compacted)) {
            throw new Exceptions\UnexpectedData("expected array, found ".Helper::getType($compacted), $compacted);
        }

        // must at least contain cacheId, key, value and timestamp
        $count = count($compacted);
        if ($count < 4) {
            throw new Exceptions\UnexpectedData("expected array with 4 elements, found ".$count, $compacted);
        }
        if (!isset($compacted[4])) {
            $compacted[] = null;
        }
        list ($cacheId, $key, $value, $ts, $dep) = $compacted;
        $item = new static($cacheId, $key, $value, $dep, $ts);
        return $item;
    }
}
