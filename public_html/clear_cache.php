<?php
// clear_cache.php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache очищен.";
} else {
    echo "OPcache не включен или функция недоступна.";
}
?>