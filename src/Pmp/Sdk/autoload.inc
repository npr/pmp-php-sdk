<?php
function PMP_SDK_default_autoload($classname)
{
  // pmp sdk classes
  if (preg_match('/^Pmp\\\\Sdk\\\\/', $classname)) {
    $parts = explode('\\', $classname);
    $fname = dirname(__FILE__) . '/' . end($parts) . '.php';
    if (file_exists($fname)) {
      require_once($fname);
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
