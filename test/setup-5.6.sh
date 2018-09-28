#!/bin/bash
pecl config-set preferred_state beta
printf "yes\\n" | pecl install -f apcu-4.0.11

