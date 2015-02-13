<?php

namespace Firelit;

class QueryIterator implements \Iterator {
	
	protected $query, $index = -1, $value, $className;

	/**
	 *	Provides an iterator interface for Query results so that
	 *	results can be returned without needing to fetch _every_ row/object.
	 *	@param Firelit\Query $query The query object to fetch results from (SQL already executed)
	 *	@param String $className The name of the class, if returning an object from record set (else returns associative array)
	 */
	public function __construct(Query $query, $className = false) {

		$this->query = $query;
		$this->className = $className;

	}

	/**
	 *	Return the current row
	 *	@return Array An associative array with the current row's value
	 */
	public function current() {

		return $this->value;

	}

	/**
	 *	Return the index of the current row
	 *	@return Int The index of the current row
	 */
	public function key() {

		return $this->index;

	}

	/**
	 *	Move forward to next row
	 *	@return Object $this For method chaining
	 */
	public function next() {

		$this->index++;

		if ($this->className) 
			$this->value = $this->query->getObject($this->className);
		else
			$this->value = $this->query->getRow();

		return $this;

	}

	/**
	 *	Rewind the Iterator to the first row (not possible with PDO query results!)
	 */
	public function rewind() {

		if ($this->index !== -1)
			throw new \Exception('Cannot rewind PDOStatement results');

		$this->next();

	}

	/**
	 *	Check if the current position is valid
	 *	@return Boolean True if valid
	 */
	public function valid() {

		return is_array($this->value) || is_object($this->value);

	}

}