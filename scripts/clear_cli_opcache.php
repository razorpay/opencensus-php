<?php
if (ini_get('opcache.enable_cli')) {
    echo "== cli opcache reset ==\n";
    // Return an error if opcache reset failed
    if (opcache_reset() === false)
    {
        exit(1);
    }
}
exit(0);
