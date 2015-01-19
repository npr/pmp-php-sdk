<?php
function PMP_SDK_default_autoload($classname)
{
    if (preg_match('#^Pmp\\\\Sdk\\\\#', $classname)) {
        $fname = str_replace('\\', '/', $classname);
        $fpath = dirname(__FILE__) . '/../../' . $fname . '.php';
        if (file_exists($fpath)) {
            require_once($fpath);
            return true;
        }
    }

    // make sure the guzzle archive has been required
    if (preg_match('/^Guzzle/', $classname)) {
        $fname = dirname(__FILE__) . '/../../guzzle.phar';
        require_once($fname);
        return true;
    }
}
spl_autoload_register('PMP_SDK_default_autoload');
