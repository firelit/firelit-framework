<?php

namespace Firelit;

class DatabaseMigrationManager {

	protected $current, $target = false, $direction, $migrations = array();
	protected $preCallback = false, $postCallback = false;

	/**
	 * Constructor
	 * @param string $currentVersion Database's current version
	 * @param string $direction Direction of migration (up or down)
	 * @param string $targetVersion The target version if down migration (up's just do everything)
	 */
	public function __construct($currentVersion, $direction = 'up', $targetVersion = false) {

		if (!in_array($direction, array('up', 'down')))
			throw new Exception('Invalid direction');

		if (($direction == 'down') && !$targetVersion)
			throw new Exception('Target version required for downward migrations');

		$this->current = $currentVersion;
		$this->target = $targetVersion;
		$this->direction = $direction;

	}

	/**
	 * submitMigration()
	 * @param string $className The name of the migration class to be checked for inclusion and execution
	 */
	public function submitMigration($className) {

		if ($this->direction == 'up') { 
			if ($className::checkVersionUp($this->current, $this->target))
				$this->addMigration(new $className);
		}

		elseif ($this->direction == 'down') {
			if ($className::checkVersionDown($this->current, $this->target)) 
				$this->addMigration(new $className);
		}

	}

	/**
	 * count()
	 * @return int Number of migrations included for execution
	 */
	public function count() {
		return sizeof($this->migrations);
	}

	/**
	 * addMigration()
	 * @param DatabaseMigration $mig Add a migration for execution
	 */
	public function addMigration(DatabaseMigration $mig) {

		$this->migrations[] = $mig;

	}

	/**
	 * sortMigrations()
	 */
	public function sortMigrations() {

		$dmm = $this;

		usort($this->migrations, function($a, $b) use ($dmm) {

			$av = $a->getVersion();
			$bv = $b->getVersion();

			if ($dmm->direction == 'up')
				return version_compare($av, $bv);
			elseif ($dmm->direction == 'down')
				return version_compare($bv, $av);

		});

	}

	/**
	 * setPreExecCallback()
	 * @param function $function Set a callback function to run before each individual migration
	 */
	public function setPreExecCallback($function) {
		$this->preCallback = $function;
	}

	/**
	 * setPostExecCallback()
	 * @param function $function Set a callback function to run after each individual migration
	 */
	public function setPostExecCallback($function) {
		$this->postCallback = $function;
	}

	/**
	 * executeMigrations()
	 */
	public function executeMigrations() {

		$count = $this->count();

		foreach ($this->migrations as $i => $mig) {

			if ($this->preCallback) {
				$callback = $this->preCallback;
				$callback($mig->getVersion(), $i, $count);
			}

			if ($this->direction == 'up')
				$mig->up();
			elseif ($this->direction == 'down')
				$mig->down();

			if ($this->postCallback) {
				$callback = $this->postCallback;
				$callback($mig->getVersion(), $i, $count);
			}

		}

	}
}