Cachet - Pluggable Caching for PHP
==================================

.. image:: https://travis-ci.org/shabbyrobe/cachet.svg?branch=master
       :target: https://travis-ci.org/shabbyrobe/cachet

::

    cachet |ˈkaʃeɪ|
    noun
      a flat capsule enclosing a dose of unpleasant-tasting medicine.

Features:

- Supports PHP 5.6 and above (5.6 support will be dropped in 2019)
- Swappable backends_
- Support for Redis_, MySQL_, APCu_, Memcached_, SQLite_ and others
- Composite backends_ for cascading_ and sharding_
- Memory efficient iteration_ for backends (wherever possible)
- Dynamic item expiration via dependencies_
- Locking_ strategies for stampede protection
- Atomic counters_
- Session_ handling
- Optional psr16-support_ via an adapter


.. contents::
    :depth: 3


Install
-------

**Cachet** can be installed using `Composer <http://getcomposer.org>`_:: 

    composer require shabbyrobe/cachet:3.0.*

You can also download **Cachet** directly from the `GitHub
Releases <https://github.com/shabbyrobe/cachet/releases>`_ page.


Usage
-----

Instantiate a backend and a cache:

.. code-block:: php
    
    <?php
    $backend = new Cachet\Backend\APCU();
    $cache = new Cachet\Cache('mycache', $backend);


Basic operations (``set``, ``get``, ``delete``, ``has``, ``flush``):

.. code-block:: php

    <?php
    $cache->set('foo', 'bar');
    $value = $cache->get('foo');
    $cache->delete('foo');
    $cache->flush();
    $exists = $cache->has('foo');
    
    // Store anything as long as it's serializable
    $cache->set('foo', array(1, 2, 3));
    $cache->set('foo', (object) array('foo'=>'bar'));
    $cache->set('foo', null);


Many "falsey" values are valid cache values, for e.g. ``null`` and ``false``.
Find out if a value was actually found:

.. code-block:: php
    
    <?php
    $cache->set('hmm', false);
    if (!$cache->get('hmm')) {
        // this will also execute if the 'false' value was actually
        // retrieved from the cache
    }
   
    $value = $cache->get('hmm', $found);
    if (!$found) {
        // this will only execute if no value was found in the cache.
        // it will not execute if values which evaluate to false were
        // retrieved from the cache.
    }

Expire data dynamically with dependencies_:
    
.. code-block:: php
    
    <?php
    // Expire in 30 seconds
    $cache->set('foo', 'bar', 30);
    
    // Expire when a file modification time is changed
    $cache->set('foo', 'bar', new Cachet\Dependency\File('/tmp/test'));
    $cache->get('foo') == 'bar';   // true
    touch('/tmp/test');
    $cache->get('foo') == 'bar';   // false


Cachet provides a convenient way to wrap getting and setting using strategies_
with optional locking_:

.. code-block:: php

    <?php
    $value = $cache->wrap('foo', function() use ($service, $param) {
        return $service->doSlowStuff($param); 
    });
   
    $dataRetriever = function() use ($db) {
        return $db->query("SELECT * FROM table")->fetchAll();
    }
    
    // With a TTL
    $value = $cache->wrap('foo', 300, $dataRetriever);
    
    // With a Dependency
    $value = $cache->wrap('foo', new Cachet\Dependency\Permanent(), $dataRetriever);
   
    // Set up a rotating pool of 4 file locks (using flock)
    $hasher = function($cache, $key) {
        return $cache->id."/".(abs(crc32($key)) % 4);
    };
    $cache->locker = new Cachet\Locker\File('/path/to/locks', $hasher);
   
    // Stampede protection - the cache will stop and wait if another concurrent process 
    // is running the dataRetriever. This means that the cache ``set`` will only happen once:
    $value = $cache->blocking('foo', $dataRetriever);


Iteration_ - this is tricky and loaded with caveats. See the iteration_ section
below that describes them in detail:

.. code-block:: php

    <?php
    $cache = new Cachet\Cache($id, new Cachet\Backend\Memory());
    $cache->set('foo', 'bar');
    
    // this dependency is just for demonstration/testing purposes.
    // iteration will not return this value as the dependency is invalid 
    $cache->set('baz', 'qux', new Cachet\Dependency\Dummy(false));
    
    foreach ($cache->values() as $key=>$value) {
        echo "$key: $value\n";
    }
    // outputs "foo: bar" only.


Atomic counters_:

.. code-block:: php

    <?php
    $counter = new Cachet\Counter\APCU();
   
    // returns 1
    $value = $counter->increment('foo');
   
    // returns 2
    $value = $counter->increment('foo');
   
    // returns 1
    $value = $counter->decrement('foo');
   
    // returns 4
    $value = $counter->increment('foo', 3);
   
    // force a counter's value
    $counter->set('foo', 100);
   
    // inspect a counter's value
    $value = $counter->value('foo');


.. _psr16-support:

PSR-16 Support
--------------

Cachet supports `PSR-16 <https://www.php-fig.org/psr/psr-16>`_, which is a PHP-FIG
recommendation for a simple caching interface. Cachet was created as an almost direct
reaction to the unreasonable overreach of the earlier `PSR-6
<https://www.php-fig.org/psr/psr-6/>`_ proposal, so it's heartening to see a better
alternative.

PSR-16 is a lowest-common-denominator attempt to provide an interface to disparate cache
APIs like Cachet, which is itself a lowest-common-denominator attempt to provide an
interface to disparate caching backends like Redis, APCU, etc, so by the time you hit an
interface like ``Psr\SimpleCache\Cache``, you've shed an awful lot of features (like
Iterators, locking, the ability to tell the difference between "null" and "not set"). I
wouldn't necessarily recommend using a PSR-16 interface over using Cachet's API directly,
but it might be useful in certain circumstances and, unlike ``PSR-6``, it's easy to
implement, so if you consider it useful, here you go!  Enjoy!

To use the adapter, create a ``Cachet\Cache`` just like usual and wrap it in a
``Cachet\Simple\Cache``:

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\APCU();
    $cache = new Cachet\Cache('mycache', $backend);
    $simple = new Cachet\Simple\Cache($cache);


.. _iteration:

Iteration
---------

Caches can be iterated, but support is patchy. If the underlying backend
supports listing keys, iteration is usually efficient. The **Cachet** APCU_
backend_ makes use of the ``APCIterator`` class and is very efficient.
Memcached_ provides no means to iterate over keys at all.

If a backend supports iteration, it will implement ``Cachet\Backend\Iterator``.
Implementing this interface is not required, but all backends provided with
**Cachet** do.  If the underlying backend doesn't support iteration (Memcache,
for example), **Cachet** provides optional support for using a secondary backend
which does support iteration for the keys. This slows down insertion, deletion
and flushing, but has no impact on retrieval.

The different types of iteration support provided by the backends are:

**iterator**
  Iteration is implemented efficiently using an ``\\Iterator`` class. Keys/items
  are only retrieved and yielded as necessary. There should be no memory issues
  with this type of iteration.

**key array + fetcher**
  All keys are retrieved in one hit. Items are retrieved one at a time directly
  from the backend.  Millions of keys may cause memory issues.

**all data**
  Everything is returned in one hit. This is only applied to the in-memory cache
  or session cache, where no other option is possible. Thousands of keys may
  cause memory issues.

**optional key backend**
  Keys are stored in a secondary iterable backend. Setting, deleting and
  flushing will be slower as these operations need to be performed on both the
  backend and the key backend. Memory issues are inherited from the key backend,
  so you should try to use an ``Iterator`` based key backend wherever possible.
  
  Key backend iteration is optional. If no key backend is supplied, iteration
  will fail.


.. _backend:
.. _backends:

Backends
--------

Cache backends must implement ``Cache\Backend``, though some backends have to
work a bit harder to satisfy the interface than others.

Different backends have varying degrees of support for the following features:

Automatic Expirations
    Some backends support automatic expiration for certain dependency_ types.
    When a backend supports this functionality it will have a
    ``useBackendExpirations`` property, which defaults to ``true``.

    For example, the APCU backend will detect when a ``Cachet\Dependency\TTL``
    is passed and automatically use it for the third parameter to
    ``apcu_store``, which accepts a TTL in seconds.  Other backends support
    different methods of unrolling dependency types. This will be documented
    below. 

    Setting ``useBackendExpirations`` to false does not guarantee the backend
    will not expire cache values under other circumstances.


Iteration
    Backends should, but may not necessarily, implement
    ``Cache\Backend\Iterator``. Backends that do not can't be iterated. This
    will be specified against each backend's documentation. Backends like APCU
    or Redis can rely on native methods for iterating over the keys, but the
    memcache daemon itself provides no such facility.

    Backends that suffer from these limitations can extend from
    ``Cachet\Backend\IterationAdapter``, which allows a second backend to be
    used for storing keys. This slows down setting, deleting and flushing, but
    doesn't slow down getting items from the backend at all so it's not a bad
    tradeoff if iteration is required and you're doing many more reads than
    writes.

    There are some potential pitfalls with this approach:

    - If an item disappears from the key backend, it may still exist in the
      backend itself. There is no way to detect these values if the backend is not
      iterable. Make sure the type of backend you select for the key backend
      doesn't auto-expire values under any circumstances, and if your backend
      supports ``useBackendExpirations``, set it to ``false``.

    - The type of backend you can use for the key backend is quite limited - it
      must itself be iterable, and it can't be a
      ``Cachet\Backend\IterationAdapter``.


.. _apc:
.. _apcu:

APCU
~~~~

This supports the ``apcu`` extension only, without the backward compatibility
functions.

For legacy code requiring ``apc`` support, use ``Cachet\Backend\APC``, though it
is deprecated. You should really upgrade to PHP >=7.0 and use ``apcu`` instead!

Iteration support
    **iterator**

Backend expirations
    ``Cachet\Expiration\TTL``

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\APCU();
    
    // Or with optional cache value prefix. Prefix has a forward slash appended:
    $backend = new Cachet\Backend\APCU("myprefix");
   
    $backend->useBackendExpirations = true; 


.. _redis:

PHPRedis
~~~~~~~~

Requires `phpredis <http://github.com/nicolasff/phpredis>`_ extension.

Iteration support
    **key array + fetcher**

Backend expirations
    - ``Cachet\Expiration\TTL``
    - ``Cachet\Expiration\Time``
    - ``Cachet\Expiration\Permanent``

.. code-block:: php
    
    <?php
    // pass Redis server name/socket as string. connect-on-demand.
    $backend = new Cachet\Backend\PHPRedis('127.0.0.1');
    
    // pass Redis server details as array. connect-on-demand. all keys
    // except host optional
    $redis = [
        'host'=>'127.0.0.1',
        'port'=>6739,
        'timeout'=>10,
        'database'=>2
    ];
    $backend = new Cachet\Backend\PHPRedis($redis);
    
    // optional cache value prefix. Prefix has a forward slash appended:
    $backend = new Cachet\Backend\PHPRedis($redis, "myprefix");
    
    // pass existing Redis instance. no connect-on-demand.
    $redis = new Redis();
    $redis->connect('127.0.0.1');
    $backend = new Cachet\Backend\PHPRedis($redis);


File
~~~~

Filesystem-backed cache. This has only been tested on OS X and Linux but may
work on Windows (and probably should - please file a bug report if it doesn't).

The cache is not particularly fast. Flushing and iteration can be very, very
slow indeed, but should not suffer from memory issues.

If you use this cache, please do some performance crunching to see if it's
actually any faster than no cache at all.

Iteration support
    **iterator**

Backend expirations
    **none**

.. code-block:: php

    <?php
    // Inherit permissions, user and group from the environment
    $backend = new Cachet\Backend\File('/path/to/cache');
    
    // Passing options
    $backend = new Cachet\Backend\File('/path/to/cache', array(
        'user'=>'foo',
        'group'=>'foo',
        'filePerms'=>0666,   // Important: must be octal
        'dirPerms'=>0777,    // Important: must be octal
    ));


.. _memcached:

Memcache
~~~~~~~~

Requires ``memcached`` PHP extension.

Iteration support
    **optional key backend**.

Backend expirations
    ``Cachet\Expiration\TTL``

.. code-block:: php

    <?php
    // Connect on demand. Constructor accepts the same argument as Memcached->addServers()
    $backend = new Cachet\Backend\Memcached(array(array('127.0.0.1', 11211)));
    
    // Use existing Memcached instance:
    $memcached = new Memcached();
    $memcached->addServer('127.0.0.1');
    $backend = new Cachet\Backend\Memcached($memcached);
   
    $backend->useBackendExpirations = true; 


Flushing is not supported by default, but works properly when a key backend is
provided. If you don't wish to use a key backend, you can activate unsafe flush
mode, which will simply flush your entire memcache instance regardless of which
cache it was called against.

.. code-block:: php

    <?php
    // using a key backend, no surprises
    $backend = new Cachet\Backend\Memcached($servers);
    $backend->setKeyBackend($keyBackend);
    
    $cache1 = new Cachet\Cache('cache1', $backend);
    $cache2 = new Cachet\Cache('cache2', $backend);
    $cache1->set('foo', 'bar');
    $cache2->set('baz', 'qux');
    
    $cache1->flush();
    var_dump($cache2->has('baz'));  // returns true
    
    
    // using unsafe flush
    $backend = new Cachet\Backend\Memcached($servers);
    $backend->unsafeFlush = true;
    
    $cache1 = new Cachet\Cache('cache1', $backend);
    $cache2 = new Cachet\Cache('cache2', $backend);
    $cache1->set('foo', 'bar');
    $cache2->set('baz', 'qux');
    
    $cache1->flush();
    var_dump($cache2->has('baz'));  // returns false!


Memory
~~~~~~

In-memory cache for the duration of the request or CLI run.

Iteration support
    **all data**

Backend expirations
    **none**

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\Memory();


.. _mysql:
.. _sqlite:

PDO
~~~

Supports MySQL and SQLite. Patches for other database support are welcome,
provided they are simple.

Iteration support
    **key array + fetcher** (or if using MySQL, optionally supports **iterator**)

Backend expirations
    **none**

.. code-block:: php
    
    <?php
    // Pass connection info array (supports connect on demand)
    $backend = new Cachet\Backend\PDO(array(
        'dsn'=>'sqlite:/tmp/pants.sqlite',
    ));
    $backend = new Cachet\Backend\PDO(array(
        'dsn'=>'mysql:host=localhost',
        'user'=>'user',
        'password'=>'password',
    ));
    
    // Pass connector function (supports connect on demand)
    $backend = new Cachet\Backend\PDO(function() {
        return new \PDO('sqlite:/tmp/pants.sqlite');
    });
    
    // Use an existing PDO (not recommended - doesn't support disconnection
    // or connect-on-demand):
    $backend = new Cachet\Backend\PDO(new PDO('sqlite:/tmp/pants.sqlite'));


The PDO backend uses a separate table for each instance of ``Cachet\Cache``. The
table name is based on the cache id prefixed with the value of
``PDO->cacheTablePrefix``, which defaults to ``cachet_``.

.. code-block:: php
 
    <?php
    $backend->cacheTablePrefix = "foo_";


Tables are not created automatically. Call this to ensure the table exists for
your cache:

.. code-block:: php
 
    <?php
    $cache = new Cachet\Cache('pants', $backend);
    $backend->ensureTableExistsForCache($cache);

If you are writing a web application, this should not be done on every request,
it should be done as part of your deployment or setup process.


The PDO backend uses a key array + fetcher for iteration by default, which is
not immune from memory exhaustion problems. The ``mysqlUnbufferedIteration``
gets rid of any memory issues and makes the ``PDO`` backend a first class
iteration citizen. The catch is that an extra connection is made to the database
each time the cache is iterated. This connection will remain open as long as the
iterator object returned by ``$backend->keys()`` or ``$backend->items()`` is in
scope.

.. code-block:: php
 
    <?php
    // Use an unbuffered query for the key iteration (MySQL only):
    $backend->mysqlUnbufferedIteration = true;

This option is disabled by default and is ignored if the underlying connector's
engine is not MySQL.


Session
~~~~~~~

Uses the PHP ``$_SESSION`` as the cache. Care should be taken to avoid unchecked
growth.  ``session_start()`` will be called automatically if it hasn't yet been
called, so if you would like to customise the session startup, call
``session_start()`` yourself beforehand.

Iteration support
    **all data**

Backend expiration
    **none**

.. code-block:: php

    <?php
    $session = new Cachet\Backend\Session();


.. _cascading:

Cascading
~~~~~~~~~

Allows multiple backends to be traversed in priority order. If a value is found
in a lower priority backend, it is inserted into every backend above it in the
list.

This works best when the fastest backend has the highest priority (earlier in
the list).

Values are set in all caches in reverse priority order.

Iteration support
    Whatever is supported by the lowest priority cache

Backend expiration
    N/A

.. code-block:: php
    
    <?php
    $memory = new Cachet\Backend\Memory();
    $apcu = new Cachet\Backend\APCU();
    $pdo = new Cachet\Backend\PDO(array('dsn'=>'sqlite:/path/to/db.sqlite'));
    $backend = new Cachet\Backend\Cascading(array($memory, $apcu, $pdo));
    $cache = new Cachet\Cache('pants', $backend);
    
    // Value is cached into Memory, APCU and PDO
    $cache->set('foo', 'bar');
    
    // Prepare a little demonstration
    $memory->flush();
    $apcu->flush();
    
    // Memory is queried and misses
    // APCU is queried and misses
    // PDO is queried and hits
    // Item is inserted into APCU
    // Item is inserted into Memory
    $cache->get('foo');


.. _sharding:

Sharding
~~~~~~~~

Allows the cache to choose one of several backends for each key. The same
backend is guaranteed to be chosen for the same key, provided the list of
backends is always the same.

Iteration support
    Each backend is iterated fully.

Backend expiration
    N/A

.. code-block:: php

    <?php
    $memory1 = new Cachet\Backend\Memory();
    $memory2 = new Cachet\Backend\Memory();
    $memory3 = new Cachet\Backend\Memory();
    
    $backend = new Cachet\Backend\Sharding(array($memory1, $memory2, $memory3));
    $cache = new Cachet\Cache('pants', $backend);
    
    $cache->set('qux', '1');
    $cache->set('baz', '2');
    $cache->set('bar', '3');
    $cache->set('foo', '4');
    
    var_dump(count($memory1->data));  // 1
    var_dump(count($memory2->data));  // 1
    var_dump(count($memory3->data));  // 2


.. _strategy:
.. _strategies:

Strategies
----------

``Cachet\Cache`` provides a series of strategy methods. Most of them require a
locker implementation to be supplied to the cache. They all follow the same
general API::

    $cache->strategyName(string $key, callable $dataRetriever);
    $cache->strategyName(string $key, int $ttl, callable $dataRetriever);
    $cache->strategyName(string $key, $dependency, callable $dataRetriever);
    
There are some minor exceptions for certain strategies which are noted below.

Most of the strategies interact with a locker_, and some strategies require that
if a backend supports ``useBackendExpirations``, that it be set to ``false``.


Wrap
~~~~

Requires locker_: **no**

Backend expirations
    **enabled or disabled**

API deviation
    **no**

The simplest caching strategy provided by **Cachet** is the ``wrap`` strategy.
It doesn't do anything to prevent stampedes, but it does not require a locker
and can make your code much more concise by reducing boilerplate. When using
``wrap``, you can turn the following code:

.. code-block:: php

    <?php
    $value = $cache->get('key', $found);
    if (!$found) {
        $value = $service->findExpensiveValue($blahBlahBlah);
        if ($value)
            $cache->set('key', $value);
    }

With this:

.. code-block:: php

    <?php
    $value = $cache->wrap('key', function() use ($service, $blahBlahBlah) {
        return $service->findExpensiveValue($blahBlahBlah);
    };

I find this dramatically improves readability by keeping the caching boilerplate
out of the way, particularly when the surrounding logic or set logic gets a
little more complicated.


Blocking
~~~~~~~~

Requires locker_
    **blocking**

Backend expirations
    **enabled or disabled**

API deviation
    **no**

This requires a locker_. In the event of a cache miss, a request will try to
acquire the lock before calling the data retrieval function. The lock will be
released after the data is retrieved. Any concurrent request which causes a
cache miss will block until the request which has acquired the lock releases it.

This strategy shouldn't be adversely affected when ``useBackendExpirations`` is
set to ``true`` if the backend supports it, though if your cache items
frequently expire after only a couple of seconds you'll probably have a bad
time.

.. code-block:: php

    <?php
    $cache->locker = create_my_locker();
    echo sprintf("%s %s start\n", microtime(true), uniqid('', true));
    $value = $cache->blocking('key', function() {
        sleep(10);
        return get_stuff();
    });
    echo sprintf("%s %s end\n", microtime(true), uniqid('', true));

The following code would output something like this (the uniqids would be
slightly more complex)::

    1381834595 1 start
    1381834599 2 start
    1381834605 1 end
    1381834605 2 end 


Safe Non Blocking
~~~~~~~~~~~~~~~~~

Requires locker_
    **non-blocking**

Backend expirations
    **must be disabled**

API deviation
    **no**

This requires a locker_. If the cache misses, the first request will acquire the
lock and run the data retriever function. Subsequent requests will return a
stale value if one is available, otherwise it will block until the first request
finishes, thus guaranteeing a value is always returned.

This strategy will fail if the backend has the ``useBackendExpirations``
property and it is set to ``true``.

.. code-block:: php

    <?php
    $cache->locker = create_my_locker();
    $value = $cache->safeNonBlocking('key', function() {
        return get_stuff();
    });


Unsafe Non Blocking
~~~~~~~~~~~~~~~~~~~

Requires locker_
    **non-blocking**

Backend expirations
    **must be disabled**

API deviation
    **yes**

This requires a locker_. If the cache misses, the first request will acquire the
lock and run the data retriever function. Subsequent requests will return a
stale value if one is available, otherwise they will return nothing immediately.

The API for this strategy is slightly different to the others as it does not
guarantee a value will be returned, so it provides an optional output parameter
``$found`` to signal that the method has returned without retrieving or setting
a value:

This strategy will fail if the backend has the ``useBackendExpirations``
property and it is set to ``true``.

.. code-block:: php

    <?php
    $cache->locker = create_my_locker();
    
    $dataRetriever = function() use ($params) {
        return do_slow_stuff($params);
    };
   
    $value = $cache->unsafeNonBlocking('key', $dataRetriever);
    $value = $cache->unsafeNonBlocking('key', $ttl, $dataRetriever);
    $value = $cache->unsafeNonBlocking('key', $dependency, $dataRetriever);
   
    $value = $cache->unsafeNonBlocking('key', $dataRetriever, null, $found);
    $value = $cache->unsafeNonBlocking('key', $ttl, $dataRetriever, $found);
    $value = $cache->unsafeNonBlocking('key', $dependency, $dataRetriever, $found);


.. _locker:
.. _lockers:
.. _locking:

Lockers
-------

Lockers handle managing synchronisation between requests in the various caching
strategies_. They must be able to support blocking on acquire, and should be
able to support a non-blocking acquire.

Lockers are passed the cache and the key when acquired by a strategy_. This can
be used raw if you want one lock for every cache key, but if you want to keep
the number of locks down, you can pass a callable as the ``$keyHasher`` argument
to the locker's constructor. You can use this to return a less complex version
of the key.

.. code-block:: php
    
    <?php
    // restrict to 4 locks per cache
    $keyHasher = function($cacheId, $key) {
        return $cacheId."/".abs(crc32($key)) % 4;
    };

.. warning:: 

    Lockers do not support timeouts. None of the current locking
    implemientations allow timeouts, so you'll have to rely on a carefully tuned
    ``max_execution_time`` property for "safety" in the case of deadlocks. This
    may change in future, but cannot change for the existing locker
    implementations until platform support improves (which it probably won't).


File
~~~~

Supported locking modes
    **blocking** or **non-blocking**

Uses ``flock`` to handle locking. Requires a dedicated, writable directory in
which locks will be stored.

.. code-block:: php
    
    <?php
    $locker = new Cachet\Locker\File('/path/to/lockfiles');
    $locker = new Cachet\Locker\File('/path/to/lockfiles', $keyHasher);

The file locker supports the same array of options as ``Cachet\Backend\File``:

.. code-block:: php

    <?php
    $locker = new Cachet\Locker\File('/path/to/lockfiles', $keyHasher, [
        'user'=>'foo',
        'group'=>'foo',
        'filePerms'=>0666,   // Important: must be octal
        'dirPerms'=>0777,    // Important: must be octal
    ]);

If the ``$keyHasher`` returns a value that contains ``/`` characters, they are
converted into path segments (i.e. ``mkdir -p``).


Semaphore
~~~~~~~~~

Supported locking modes
    **blocking**

Uses PHP's `semaphore <http://php.net/manual/en/book.sem.php>`_ functions to
provide locking. PHP must be compiled with ``--enable-sysvsem`` for this to
work.

This locker **does not** support non-blocking acquire.

.. code-block:: php

    <?php
    $locker = new Cachet\Locker\Semaphore($keyHasher);


.. _dependency:
.. _dependencies:

Dependencies
------------

**Cachet** supports the notion of cache dependencies - an object implementing
``Cachet\Dependency`` is serialised with your cache value and checked on
retrieval. Any serialisable code can be used in a dependency, so this opens up a
large range of invalidation possibilities beyond what TTL can accomplish.

Dependencies can be passed per-item using ``Cachet\Cache->set($key, $value,
$dependency)``, or using the ``Cachet\Cache->set($key, $value, $ttl)``
shorthand. The shorthand is equivalent to ``$cache->set($key, $value, new
Cachet\Dependency\TTL($ttl))``.

Without a dependency, a cached item will stay cached until it is removed
manually or until the underlying backend decides to remove it of its own accord.

You can assign a dependency to be used as the default for an entire cache if
none is provided for an item:

.. code-block:: php
    
    <?php
    $cache = new Cachet\Cache($name, $backend);
    
    // all items that do not have a dependency will expire after 10 minutes
    $cache->dependency = new Cachet\Dependency\TTL(600);
    
    // this item will expire after 10 minutes
    $cache->set('foo', 'bar');
    
    // this item will expire after 5 minutes
    $cache->set('foo', 'bar', new Cachet\Dependency\TTL(300));


.. warning::

    Just because an item has expired does not mean it has been removed. Expired
    items will be removed on retrieval, but garbage collection is a manual
    process that should be performed by a separate process.
    

TTL
~~~

.. code-block:: php
    
    <?php
    // cache for 5 minutes
    $cache->set('foo', 'bar', new Cachet\Dependency\TTL(300));


Permanent
~~~~~~~~~

A cached item will never be expired by **Cachet**, even if a default dependency
is provided by the Cache. This may be overridden by any environment-specific
backend configuration (for example, the `apc.ttl
<http://php.net/manual/en/apcu.configuration.php#ini.apcu.ttl>`_ ini setting):

.. code-block:: php

    <?php
    $cache = new Cachet\Cache($name, $backend);
    $cache->dependency = new Cachet\Dependency\TTL(600);
    
    // this item will expire after 10 minutes
    $cache->set('foo', 'bar');
   
    // this item will never expire
    $cache->set('foo', 'bar', new Cachet\Dependency\Permanent());


Time
~~~~

The item is considered invalid at a fixed timestamp:

.. code-block:: php

    <?php
    $cache->set('foo', 'bar', new Cachet\Dependency\Time(strtotime('Next week')));


Mtime
~~~~~

Supports invalidating items cached based on a file modification time.

.. code-block:: php
    
    <?php
    $cache->set('foo', 'bar', new Cachet\Dependency\Mtime('/path/to/file');
    $cache->get('foo'); // returns 'bar'
    
    touch('/path/to/file');
    $cache->get('foo'); // returns null


Cached Tag
~~~~~~~~~~

This is very similar to the ``Mtime`` dependency, only instead of using simple
file mtimes, it uses a secondary cache and checks that the value of a tag has
not changed.

This dependency is slightly more complicated to configure - it requires the
secondary cache to be registered with the primary cache as a service.

.. code-block:: php

    <?php
    $valueCache = new Cachet\Cache('value', new Cachet\Backend\APCU());
    $tagCache = new Cachet\Cache('value', new Cachet\Backend\APCU());
    
    $tagCacheServiceId = 'tagCache';
    $valueCache->services[$tagCacheServiceId] = $tagCache;
    
    // the value at key 'tag' in $tagCache is stored alongside 'foo'=>'bar' in the
    // $valueCache. It will be checked against whatever is currently in $tagCache
    // on retrieval
    $valueCache->set('foo', 'bar', new Cachet\Dependency\CachedTag($tagCacheServiceId, 'tag'));
    $valueCache->set('baz', 'qux', new Cachet\Dependency\CachedTag($tagCacheServiceId, 'tag'));
    
    // 'tag' has not changed in $tagCache since we set these values in $valueCache
    $valueCache->get('foo');  // returns 'bar'
    $valueCache->get('baz');  // returns 'qux'
    
    $tagCache->set('tag', 'something else');
    
    // 'tag' has since changed, so the values coming out of $valueCache are invalidated
    $valueCache->get('foo');  // returns null
    $valueCache->get('baz');  // returns null
    

Equality comparison is done in loose mode by default (``==``). You can enable
strict mode comparison by passing a third boolean argument to the constructor:

.. code-block:: php

    <?php
    $dependency = new Cachet\Dependency\CachedTag($tagCacheServiceId, 'tag', !!'strict');

Strict mode uses ``===`` for everything except objects, for which it uses
``==``. This is because ``===`` will never match ``true`` for objects as it
compares references only; the values to be compared have each been retrieved
from separate caches so they are highly unlikely to ever share a reference.


Composite
~~~~~~~~~

Checks many dependencies. Can be set to be valid when any dependency is valid,
or when all dependencies are valid.

**All** mode: the following will be considered valid if **both** the item is
less than 5 minutes old **and** the file ``/path/to/file`` has not been touched.

.. code-block:: php

    <?php
    $cache->set('foo', 'bar', new Cachet\Dependency\Composite('all', array(
        new Cachet\Dependency\Mtime('/path/to/file'),
        new Cachet\Dependency\TTL(300),
    ));


**Any** mode: The following will be considered valid when **either** the item is
less than 5 minutes old **or** the file ``/path/to/file`` has not been touched.

.. code-block:: php

    <?php
    $cache->set('foo', 'bar', new Cachet\Dependency\Composite('any', array(
        new Cachet\Dependency\Mtime('/path/to/file'),
        new Cachet\Dependency\TTL(300),
    ));


.. _session:

Session Handler
---------------

``Cachet\Cache`` can be registered to handle PHP's ``$_SESSION`` superglobal:

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\PDO(['dsn'=>'sqlite:/path/to/sessions.sqlite']);
    $cache = new Cachet\Cache('session', $backend);
    
    // this must be called before session_start()
    Cachet\SessionHandler::register($cache);
    
    session_start();
    $_SESSION['foo'] = 'bar';


By default, ``Cachet\SessionHandler`` does nothing when the ``gc`` (garbage
collect) method is called. This is because cache iteration can't be relied upon
to be performant - this is a backend specific characteristic and can vary wildly
(see the iteration_ section for more details) and it is up to the developer to
be aware of this when selecting a backend. 

You can activate automatic garbage collection like so:

.. code-block:: php

    <?php
    Cachet\SessionHandler::register($cache, ['runGc'=>true]);
    
    // or...
    Cachet\SessionHandler::register($cache);
    Cachet\SessionHandler::$instance->runGc = true;


For backends that don't use an ``Iterator`` for iteration_, it is **strongly**
recommended that you implement garbage collection using a separate process
rather than using PHP's gc probability mechanism.

The following backends should **not** be used with the ``SessionHandler``:

``Cachet\Backend\File``
    This will raise a warning. I can't see any way that PHP's default file
    session mechanism isn't superior to this backend - they essentially do the
    same thing only one is implemented in C and seriously battle tested, and the
    other is not.

``Cachet\Backend\Session``
    This will raise an exception. You can't use the session for storing
    sessions.

``Cachet\Backend\Memory``
    This can't possibly work either - the data will disappear when the request
    is complete.


.. _counter:
.. _counters:

Counters
--------

Some backends provide methods for incrementing or decrementing an integer
atomically. Cachet attempts to provide a consistent interface to this
functionality.

Unfortunately, it doesn't always succeed. There are some catches (like always):

- In some cases, though the backend's increment and decrement methods work
  atomcally, they require you to set the value before you can use it in a way
  which is not atomic. The **Cachet** counter interface allows you to call
  increment if there is no value already set.

  Unfortunately, this means that multiple concurrent processes can call
  ``$backend->increment()`` and see that nothing is there before one of those
  processes has a chance to call ``set`` to initialise the counter. Counters
  that exhibit this behaviour can be passed an optional locker_ to mitigate this
  problem.

- All of the backends support decrementing below zero except Memcache.

- Several backends have limits on the maximum counter value and will overflow if
  this value is reached. There has not been enough testing done yet to determine
  what the maximum value for each counter backend is, and it may be platform and
  build dependent. An estimate has been provided, but this is based on the ARM
  architeture. YMMV.

- Counters do not support dependencies, but some counters do allow a single TTL
  to be specified for all counters. This is indicated by the presence of a
  ``$backend->counterTTL`` property.

- There does exist the fabled Counter class that is atomic, does not overflow
  and supports any type of cache dependency (``Cachet\Counter\SafeCache``).
  Unfortunately, it is *slow* and it requires a locker. Fast, secure, cheap,
  stable, good. Pick two.

Why aren't counters just a part of ``Cachet\Cache``? I tried to do it that way
first, but after spending a bit of time hacking and unable to escape the feeling
that I was wrecking things that were nice and clean to support it, I realised
that it was a separate responsibility deserving its own hierarchy. There also
isn't a clean 1-to-1 relationship between counters and backends.

Counters implement the ``Cachet\Counter`` interface, and support the following
API:

.. code-block:: php

    <?php
    // You can increment an uninitialised counter:
    // $value == 1
    $value = $counter->increment('foo');
   
    // You can also increment by a custom step value:
    // $value == 5
    $value = $counter->increment('foo', 4);
   
    // $value = 4
    $decremented = $counter->decrement('foo');
   
    // $value = 1
    $decremented = $counter->decrement('foo', 3);
   
    // $value = 1
    $value = $counter->value('foo');
   
    $counter->set('foo', 100);


APCU
~~~~

This supports the ``apcu`` extension only, without the backward compatibility
functions.

For legacy code requiring ``apc`` support, use ``Cachet\Counter\APC``, though it
is deprecated. You should really upgrade to PHP >=7.0 and use ``apcu`` instead!

Supports ``counterTTL``
    **yes**

Atomic
    **partial**. **full** with optional locker_

Range
    ``-PHP_INT_MAX - 1`` to ``PHP_INT_MAX``

Overflow error
    **no**

.. code-block:: php

    <?php
    $counter = new \Cachet\Counter\APCU();
   
    // Or with optional cache value prefix. Prefix has a forward slash appended.
    $counter = new Cachet\Counter\APCU('myprefix');
   
    // TTL
    $counter->counterTTL = 86400;
   
    // If you would like set operations to be atomic, pass a locker to the constructor
    // or assign to the ``locker`` property
    $counter->locker = new \Cachet\Locker\Semaphore();
    $counter = new \Cachet\Counter\APCU('myprefix', \Cachet\Locker\Semaphore());


PHPRedis
~~~~~~~~

Supports ``counterTTL``
    **no**

Atomic
    **yes**

Range
    ``-INT64_MAX - 1`` to ``INT64_MAX``

Overflow error
    **yes**

.. code-block:: php

    <?php
    $redis = new \Cachet\Connector\PHPRedis('127.0.0.1');
    $counter = new \Cachet\Counter\PHPRedis($redis);
   
    // Or with optional cache value prefix. Prefix has a forward slash appended.
    $counter = new \Cachet\Counter\PHPRedis($redis, 'prefix');

Redis itself does support applying a TTL to a counter, but I haven't come up
with the best way to implement it atomically yet. Consider it a work in
progress.


Memcache
~~~~~~~~

Supports ``counterTTL``
    **yes**

Atomic
    **partial**. **full** with optional locker_

Range
    ``-PHP_INT_MAX - 1 to PHP_INT_MAX``

Overflow error
    **no**

.. code-block:: php
    
    <?php
    // Construct by passing anything that \Cachet\Connector\Memcache accepts as its first
    // constructor argument:
    $counter = new \Cachet\Counter\Memcache('127.0.0.1');
   
    // Construct by passing in a connector. This allows you to share a connector instance 
    // with a cache backend:
    $memcache = new \Cachet\Connector\Memcache('127.0.0.1');
    $counter = new \Cachet\Counter\Memcache($memcache);
    $backend = new \Cachet\Backend\Memcache($memcache);
    
    // Optional cache value prefix. Prefix has a forward slash appended.
    $counter = new \Cachet\Counter\Memcache($memcache, 'prefix');
   
    // TTL
    $counter->counterTTL = 86400;
   
    // If you would like set operations to be atomic, pass a locker to the constructor
    // or assign to the ``locker`` property
    $counter->locker = $locker;
    $counter = new \Cachet\Counter\Memcache($memcache, 'myprefix', $locker);


PDOSQLite and PDOMySQL
~~~~~~~~~~~~~~~~~~~~~~

Unlike the PDO cache backend, different database engines require very different
queries for counter operations. If your PDO engine is sqlite, use
``Cachet\Counter\PDOSQLite``. If your PDO engine is MySQL, use
``Cachet\Counter\PDOMySQL``. ``PDOSQLite`` may be compatible with other database
backends (though this is untested), but ``PDOMySQL`` uses MySQL-specific
queries.

The table name defaults to ``cachet_counter`` for all counters. This can be changed.

Suports ``counterTTL``
    **no**

Atomic
    **probably** (I haven't been able to satisfy myself that I have proven this yet)

Range
    ``-INT64_MAX - 1 to INT64_MAX``

Overflow error
    **no**

.. code-block:: php

    <?php
    // Construct by passing anything that \Cachet\Connector\PDO accepts as its first
    // constructor argument:
    $counter = new \Cachet\Counter\PDOSQLite('sqlite::memory:');
    $counter = new \Cachet\Counter\PDOMySQL([
        'dsn'=>'mysql:host=localhost', 'user'=>'user', 'password'=>'password'
    ]);
   
    // Construct by passing in a connector. This allows you to share a connector instance 
    // with a cache backend:
    $connector = new \Cachet\Connector\PDO('sqlite::memory:');
    $counter = new \Cachet\Counter\PDOSQLite($connector);
   
    $connector = new \Cachet\Connector\PDO(['dsn'=>'mysql:host=localhost', ...]);
    $counter = new \Cachet\Counter\PDOMySQL($connector);
   
    $backend = new \Cachet\Backend\PDO($connector);
   
    // Use a specific table name
    $counter->tableName = 'my_custom_table';
    $counter = new \Cachet\Counter\PDOSQLite($connector, 'my_custom_table');
    $counter = new \Cachet\Counter\PDOMySQL($connector, 'my_custom_table');


The table needs to be initialised in order to be used. It is not recommended to
do this inside your web application - you should do it as part of your
deployment process or application setup:

.. code-block:: php

    <?php
    $counter->ensureTableExists();


SafeCache
~~~~~~~~~

Supports ``counterTTL``
    **yes**, via ``$counter->cache->dependency``

Atomic
    **yes**

Range
    unlimited

This counter simply combines a ``Cachet\Cache`` with a locker_ and either
``bcmath`` or ``gmp`` to get around the atomicity and range limitations of the
other counters.

It also supports dependencies_ of any type.

It is a lot slower than using the APCU or Redis backends, but faster than using
the PDO-based backends (unless, of course, the cache that you use has a
PDO-based backend itself).

.. code-block:: php

    <?php
    $cache = new \Cachet\Cache('counter', $backend);
    $locker = new \Cachet\Locker\Semaphore();
    $counter = new \Cachet\Counter\SafeCache($cache, $locker);
   
    // Simulate counterTTL
    $cache->dependency = new \Cachet\Dependency\TTL(3600);
   
    // Or use any dependency you like
    $cache->dependency = new \Cachet\Dependency\Permanent();


Extending
---------

Backends
~~~~~~~~

Custom backends are a snap to write - simply implement ``Cachet\Backend``.
Please make sure you follow these guidelines:

- Backends aren't meant to be used by themselves - they should be used by an
instance of ``Cachet\Cache``

- It must be possible to use the same backend instance with more than one
instance of ``Cachet\Cache``.

- ``get()`` must return an instance of ``Cachet\Item``. The backend must not
check whether an item is valid as ``Cachet\Cache`` depends on an item always
being returned.

- Make sure you fully implement ``get()``, ``set()`` and ``delete()`` at
minimum. Anything else is not strictly necessary, though useful.

- ``set()`` must store enough information so that ``get()`` can return a fully
populated instance of ``Cachet\Item``. This usually means that if your backend
can't support PHP objects directly, you should just ``serialize()`` the
``Cachet\Item`` directly.

You can reduce the size of the data placed into the backend by using
``Cachet\Item->compact()`` and ``Cachet\Item::uncompact()``. This strips much of
the redundant information from the cache item.  YMMV - I was surprised to find
that using ``Cachet\Item->compact()`` had the effect of *increasing* the memory
used in APCU.


Dependencies
~~~~~~~~~~~~

Dependencies are created by implementing ``Cachet\Dependency``. Dependencies are
serialised and stored in the cacne alongside the value. A dependency is always
passed a reference to the current cache when it is used, and care should be
taken never to hold a reference to it, or any other objects that don't directly
relate to the dependency's data as they will also be shoved into the cache, and
trust me - you don't want that.


Development
-----------

Testing
~~~~~~~

**Cachet** is exhaustively tested. As all backends and counters are expected to
satisfy the same interface, for all but a very small number of (hopefully)
well-documented exceptions, all of the functional test cases for these classes
extend from ``Cachet\Test\BackendTestCase`` and ``Cachet\Test\CounterTestCase``
respectively.

These tests are run from the root of the project by calling ``phpunit`` without
arguments.

Some aspects of **Cachet** cannot be proven to work using simple unit or
functional tests, for example lockers_ and counter_ atomicity. These are tested
using a hacky but workable concurrency tester, which is run from the root of the
project. You can get help on all of the available options like so::

    php test/concurrent.php -h

Or just call it without arguments to run all of the concurrency tests using the
default settings. It will exit with status ``0`` if all tests pass, or ``1`` if
any of them fail.

Some of the tests are designed to fail, but these contain ``broken`` in their
ID. You can exclude unsafe tests like so::

    php test/concurrent.php -x broken

I have left the broken tests in to demonstrate conditions where the default
behaviour may defy expectations. I am currently looking for a better way of
reperesenting this in the tester.

The concurrency tester has proven to be excellent at finding heisenbugs in
**Cachet**. For this reason, it should be run many, many times under several
different load conditions and on different architectures before we can decide
that a build is safe to release.


License
-------

**Cachet** is licensed under the MIT License. See ``LICENSE`` for more info.

