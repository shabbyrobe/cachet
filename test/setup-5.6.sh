#!/bin/bash
pecl config-set preferred_state beta
printf \"yes\n\" | pecl install apcu

