<?php
function PMP_SDK_default_autoload($classname) {

  // pmp sdk classes
  if (preg_match('/^Pmp\\\\Sdk\\\\/', $classname)) {
    $parts = explode('\\', $classname);
    $fname = dirname(__FILE__) . '/' . end($parts) . '.php';
    if (file_exists($fname)) {
      require_once($fname);
      return true;
    }
  }

  // restagent
  if (preg_match('/^restagent/', $classname)) {
    $fname = dirname(__FILE__) . '/../../restagent/restagent.lib.php';
    require_once($fname);
    return true;
  }

  // guzzle (uri template only)
  if (preg_match('/^Guzzle/', $classname)) {
    $fpath = dirname(__FILE__) . '/../../guzzle';
    require_once($fpath . '/UriTemplateInterface.php');
    require_once($fpath . '/UriTemplate.php');
    return true;
  }

}

// only use autoloader if vendor'd one isn't there
if (file_exists(dirname(__FILE__) . '/../../../vendor/autoload.php')) {
  require_once(dirname(__FILE__) . '/../../../vendor/autoload.php');
}
else {
  spl_autoload_register('PMP_SDK_default_autoload');
}
