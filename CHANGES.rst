Significant Changes
===================

v4.0.0
------

- PHP 8.0 supported
- EOL PHP versions (5.x, 7.0, 7.1, 7.2) no longer supported


v3.0.0
------

``Cachet\Backend\APC`` removed, ``Cachet\Backend\XCache`` removed.  If you need
these two backends, version 2 will still work. Support for PHP < 5.6 has been dropped.
Added an adapter to support PSR-16's ``SimpleCache`` via
``Cachet\Simple\Cache``.


v2.0.1
------

``Cachet\Backend\Memcache`` used to have some possible, untested, undocumented
support for the abandoned ``memcache`` extension as well as the actively supported
``memcached`` extension. ``memcache`` support has been removed, ``memcached`` support
remains.


v2.0
----

``Cachet\Backend\APC`` and ``Cachet\Counter\APC`` are deprecated due to all PHP versions
that don't support opcache being EOL. Use ``Cachet\Backend\APCU`` and
``Cachet\Counter\APCU`` instead. The old classes will not be removed for the time being.

``Cachet\Backend\Iterable`` renamed to ``Cachet\Backend\Iterator`` in response to PHP 7.1
backward incompatible changes.

