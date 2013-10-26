<?php

namespace('Firelit');

class VarsStoreFile extends VarsStore {
	
	private $file;
	
	public function __construct($fileName) {
		
		$this->file = $fileName;	
		
	}
	
	public function set($name, $value) {
		
		$current = $this->get($name);
		
		if (is_null($value)) unset($current[$name]);
		else $current[$name] = $value;
		
		$res = file_put_contents($this->file, serialize($current));
		
		if ($res === false) 
			throw new \Exception('Error saving var data to file.');
			
	}
	
	public function get($name) {
		
		$data = file_get_contents($this->file);
		
		if ($data === false)
			throw new \Exception('Error reading var data from file.');
			
		return unserialize($data);
		
	}
	
}