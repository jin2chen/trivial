<?php
abstract class Singleton
{
	private static $_instance = array();
	
	final public function getInstance()
	{
		$className = get_called_class();

		if (!isset(self::$_instance[$className])) {
			self::$_instance[$className] = new static();
			self::$_instance[$className]->init();
		}
		
		return self::$_instance[$className];
	}
	
	public function init()
	{
	}
	
	final private function __clone()
	{
	}
}