<?php
require __DIR__.'/config.php';

echo serialize(iterator_to_array($backend->items('session')));
