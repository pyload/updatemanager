<?php
define('PLUGIN_LIST', getenv('OPENSHIFT_DATA_DIR') . 'plugins.txt');
define('LAST_VERSION', '0.4.20');

if (! isset($_GET['v'])) {
    exit('ERROR: No version specified!');
}

$v = trim($_GET['v']);

if(version_compare(LAST_VERSION, $v) == 1) {
    print(LAST_VERSION);
}
else {
    $f = fopen(PLUGIN_LIST, 'r');
    print(fread($f, filesize(PLUGIN_LIST)));
    fclose($f);
}
?>
