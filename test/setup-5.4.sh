#!/bin/bash
pecl config-set preferred_state beta
printf \"yes\n\" | pecl install apcu

xcache_version=3.1.0
wget http://xcache.lighttpd.net/pub/Releases/${xcache_version}/xcache-${xcache_version}.tar.gz
tar -xvf xcache-${xcache_version}.tar.gz
cd xcache-${xcache_version}
phpize
./configure --enable-xcache
make
sudo make install

