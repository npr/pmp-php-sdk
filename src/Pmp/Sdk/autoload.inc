<?php
function PMP_SDK_default_autoload($classname)
{
	$parts = explode('\\', $classname);
	$filename = dirname(__FILE__) . "/" . end($parts) . ".php";
	if(file_exists($filename))
	{
		require_once($filename);
		return true;
	}
}
spl_autoload_register('PMP_SDK_default_autoload');