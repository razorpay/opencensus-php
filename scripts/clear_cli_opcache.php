<?php

if (ini_get('opcache.enable_cli')) {
    echo "== cli opcache reset ==\n";
    opcache_reset();
}
