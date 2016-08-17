<?php namespace June\Core;

abstract class Registry {
	abstract function get($key);
    abstract function set($key, $val);
}
?>