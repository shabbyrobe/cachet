<?php
namespace Cachet\Test\Counter;

// TODO: should test both bcmath and gmp versions
if (!extension_loaded('bcmath') || !extension_loaded('gmp')) {
    skip_test(__NAMESPACE__, "SafeCacheTest", "Neither bcmath nor gmp loaded");
}
else {
    /**
     * @group counter
     */
    class SafeCacheTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $cache = new \Cachet\Cache('counter', new \Cachet\Backend\Memory());
            $locker = $this->getMockForAbstractClass('Cachet\Locker');
            return new \Cachet\Counter\SafeCache($cache, $locker);
        }
       
        /**
         * @dataProvider dataForRangeInfinite
         */
        public function testRangeInfinite($a, $b)
        {
            $counter = $this->getCounter();
            $result = $counter->increment('count', $a);
            $this->assertEquals($b, $a);
            $this->assertEquals($b, $counter->value('count'));
        }

        public function dataForRangeInfinite()
        {
            return [
                ["12093810298309182930812038128030912839", "12093810298309182930812038128030912840"],
                ["-12093810298309182930812038128030912839", "-12093810298309182930812038128030912838"],
                [
                    "1211029380129381029381092380093810298309182930812038128030912839",
                    "1211029380129381029381092380093810298309182930812038128030912840"
                ],
                [
                    "-1211029380129381029381092380093810298309182930812038128030912839",
                    "-1211029380129381029381092380093810298309182930812038128030912838"
                ],
            ];
        }
    }
}

