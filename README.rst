Cachet - Pluggable Caching for PHP
==================================

::

    cachet |ˈkaʃeɪ|
    noun
      a flat capsule enclosing a dose of unpleasant-tasting medicine.


.. contents::
    :depth: 3


Usage
-----

Optional autoloader. **Cachet** is compatible with any PSR-0 autoloader.

.. code-block:: php

    <?php
    require 'path/to/cachet/src/Cachet.php';
    Cachet::register();


Instantiate a backend and a cache:

.. code-block:: php
    
    <?php
    $backend = new Cachet\Backend\APC();
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


Many "falsey" values are valid cache values, for e.g. ``null`` and ``false``. Find out if a value
was actually found:

.. code-block:: php
    
    <?php
    $value = $cache->get('doesntExist', $found);
    var_dump($found);


Dependencies <dependencies>:
    
.. code-block:: php
    
    <?php
    // Expire in 30 seconds
    $cache->set('foo', 'bar', 30);
    
    // Expire when a file modification time is changed
    $cache->set('foo', 'bar', new Cachet\Dependency\File('/tmp/test'));
    $cache->get('foo') == 'bar';   // true
    touch('/tmp/test');
    $cache->get('foo') == 'bar';   // false


Cache wrapper method - pass a callback that is called if the key is not found in the cache. The
returned value is stored in the cache:

.. code-block:: php

    <?php
    $value = $cache->wrap('foo', function() use ($db) {
        return $db->query("SELECT * FROM table")->fetchAll();
    });

    // With a TTL
    $value = $cache->wrap('foo', 300, function() use ($db) {
        return $db->query("SELECT * FROM table")->fetchAll();
    });
    
    // With a Dependency
    $value = $cache->wrap('foo', new Cachet\Dependency\Permanent(), function() use ($db) {
        return $db->query("SELECT * FROM table")->fetchAll();
    });


Cache options and their defaults:

.. code-block:: php
    
    <?php
    // delete items if they are in an invalid format
    $cache->deleteIfInvalid = true;


Backends
--------

APC
~~~

Works with ``apc`` and ``apcu``.

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\APC();
    
    // Or with optional cache value prefix. Prefix has a forward slash appended:
    $backend = new Cachet\Backend\APC("myprefix");


File
~~~~

Filesystem-backed cache. This has only been tested on OS X and Linux but may work on Windows (and
probably should).

The cache is not particularly fast, and flushing can be very, very slow indeed. If you use this
cache, do some performance crunching to see if it's actually any faster than no cache at all.

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


Memcached
~~~~~~~~~

Requires ``memcached`` PHP extension.
 
.. code-block:: php

    <?php
    // Connect on demand:
    $backend = new Cachet\Backend\Memcached(array('127.0.0.1'));
    
    // Use existing Memcached instance:
    $memcached = new Memcached();
    $memcached->addServer('127.0.0.1');
    $backend = new Cachet\Backend\Memcached($memcached);


Memory
~~~~~~

In-memory cache for the duration of the request or CLI run.

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\Memory();


PDO
~~~

Supports MySQL and SQLite. Patches for other database support are welcome, provided they are simple.

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


PHPRedis
~~~~~~~~

Requires `phpredis <http://github.com/nicolasff/phpredis>`_ extension.

.. code-block:: php
    
    <?php
    $redis = new Redis();
    $redis->connect('127.0.0.1');
    $backend = new Cachet\Backend\PHPRedis($redis);
    
    // Or with optional cache value prefix. Prefix has a forward slash appended:
    $backend = new Cachet\Backend\PHPRedis($redis, "myprefix");


Session
~~~~~~~

Uses the PHP ``$_SESSION`` as the cache. Care should be taken to avoid unchecked growth. 
``session_start()`` will be called automatically if it hasn't yet been called, so if you would
like to customise the session startup, call ``session_start()`` yourself beforehand.

.. code-block:: php

    <?php
    $session = new Cachet\Backend\Session();


XCache
~~~~~~

.. code-block:: php

    <?php
    $backend = new Cachet\Backend\XCache();
    
    // Or with optional cache value prefix. Prefix has a forward slash appended:
    $backend = new Cachet\Backend\XCache("myprefix");


Cascading
~~~~~~~~~

Allows multiple backends to be traversed in priority order. If a value is found in a lower priority
backend, it is inserted into every backend above it in the list.

This works best when the fastest backend has the highest priority (earlier in the list).

Values are set in all caches in reverse priority order.

.. code-block:: php
    
    <?php
    $memory = new Cachet\Backend\Memory();
    $apc = new Cachet\Backend\APC();
    $pdo = new Cachet\Backend\PDO(array('dsn'=>'sqlite:/path/to/db.sqlite'));
    $backend = new Cachet\Backend\Cascading(array($memory, $apc, $pdo));
    $cache = new Cachet\Cache('pants', $backend);
    
    // Value is cached into Memory, APC and PDO
    $cache->set('foo', 'bar');
    
    // Prepare a little demonstration
    $memory->flush();
    $apc->flush();
    
    // Memory is queried and misses
    // APC is queried and misses
    // PDO is queried and hits
    // Item is inserted into APC
    // Item is inserted into Memory
    $cache->get('foo');


Sharding
~~~~~~~~

Allows the cache to choose one of several backends for each key. The same backend is guaranteed to
be chosen for the same key, provided the list of backends is always the same.

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


Custom
~~~~~~

Custom backends are a snap to write - simply implement ``Cachet\Backend``. Please make sure you
follow these guidelines:

- Backends aren't meant to be used by themselves - they should be used by an instance of
  ``Cachet\Cache``

- It must be possible to use the same backend with more than one instance of ``Cachet\Cache``.

- ``get()`` must return an instance of ``Cachet\Item``. You are not required to check whether it
  is valid, ``Cachet\Cache`` does this for you.

- Make sure you fully implement ``get()``, ``set()`` and ``delete()`` at minimum. Anything else is
  not strictly necessary.

- ``set()`` must store enough information so that ``get()`` can return a fully populated instance
  of ``Cachet\Item``. This usually means that if your backend can't support PHP objects directly,
  you should just ``serialize()`` the ``Cachet\Item`` directly.

You can reduce the size of the data placed into the backend by using ``Cachet\Item->compact()``
and ``Cachet\Item::uncompact()``. This strips much of the redundant information from the cache item.
YMMV - I was surprised to find that using ``Cachet\Item->compact()`` had the effect of *increasing*
the memory used in APCU.


Dependencies
------------

``Cachet\Cache`` supports passing a TTL (time to live) in seconds to ``set()``. Many backends
support TTL directly and will garbage collect values for you, so TTL should be used wherever
practicable, however it is not adequate for all use cases.

**Cachet** supports the notion of cache dependencies - an object implementing ``Cachet\Dependency``
is serialised with your cache value and checked on retrieval. Any serialisable code can be used in
a dependency, so this opens up a large range of invalidation possibilities beyond what TTL can
accomplish.

Dependencies can be passed per-item using ``Cachet\Cache->set($key, $value, $dependency)``, or
using the ``Cachet\Cache->set($key, $value, $ttl)`` shorthand. The shorthand is equivalent to
``$cache->set($key, $value, new Cachet\Dependency\TTL($ttl))``.

Without a dependency, a cached item will stay cached until it is removed.

You can assign a dependency to be used as the default for an entire cache if none is provided for
an item:

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

    Just because an item has expired does not mean it has been removed. Expired items will be 
    removed on retrieval, but garbage collection is a manual process for now and can only really
    be performed on backends that support iteration (Memcache does not, for example).
    
    Some way to manage garbage collection and key iteration is on my TODO list.


TTL
~~~

.. code-block:: php
    
    <?php
    // cache for 5 minutes
    $cache->set('foo', 'bar', new Cachet\Dependency\TTL(300));


Permanent
~~~~~~~~~

A cached item will never be expired by **Cachet**, even if a default dependency is provided by the
Cache. This may be overridden by any environment-specific backend configuration (for example, the
`apc.ttl <http://www.php.net/manual/en/apc.configuration.php#ini.apc.ttl>`_ ini setting):

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

This is very similar to the ``Mtime`` dependency, only instead of using simple file mtimes, it uses
a secondary cache and checks that the value of a tag has not changed.

This dependency is slightly more complicated to configure - it requires the secondary cache to be
registered with the primary cache as a service.

.. code-block:: php

    <?php
    $valueCache = new Cachet\Cache('value', new Cachet\Backend\APC());
    $tagCache = new Cachet\Cache('value', new Cachet\Backend\APC());
    
    $valueCache->services['tagCache'] = $tagCache;
    
    // the value at key 'tag' in $tagCache is stored alongside 'foo'=>'bar' in the
    // $valueCache. It will be checked against whatever is currently in $tagCache
    // on retrieval
    $valueCache->set('foo', 'bar', new Cachet\Dependency\CachedTag('tagCache', 'tag'));
    $valueCache->set('baz', 'qux', new Cachet\Dependency\CachedTag('tagCache', 'tag'));
    
    // 'tag' has not changed in $tagCache since we set these values in $valueCache
    $valueCache->get('foo');  // returns 'bar'
    $valueCache->get('baz');  // returns 'qux'
    
    $tagCache->set('tag', 'something else');
    
    // 'tag' has since changed, so the values coming out of $valueCache are invalidated
    $valueCache->get('foo');  // returns null
    $valueCache->get('baz');  // returns null
    

Composite
~~~~~~~~~

Checks many dependencies. Can be set to be valid when any dependency is valid, or when all
dependencies are valid.

The following will be considered valid only if the item is less than 5 minutes old and the file
``/path/to/file`` has not been touched.

.. code-block:: php

    <?php
    $cache->set('foo', 'bar', new Cachet\Dependency\Composite('all', array(
        new Cachet\Dependency\Mtime('/path/to/file'),
        new Cachet\Dependency\TTL(300),
    ));
    

License
-------

**Cachet** is licensed under the MIT License. See ``LICENSE`` for more info.

