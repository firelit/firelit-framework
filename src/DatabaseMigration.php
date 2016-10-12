<?php

namespace Firelit;

abstract class DatabaseMigration
{
    
    static protected $version;

    abstract public function up();

    abstract public function down();

    public static function getVersion()
    {
        return static::$version;
    }

    public static function checkVersionUp($currentVersion, $targetVersion = false)
    {
        $comp = version_compare($currentVersion, static::$version);
        if (!$targetVersion) {
            return ($comp == -1);
        } elseif ($comp >= 0) {
            return false;
        }
        $comp = version_compare($targetVersion, static::$version);
        return ($comp >= 0);
    }

    public static function checkVersionDown($currentVersion, $targetVersion)
    {
        $comp = version_compare($currentVersion, static::$version);
        if ($comp < 0) {
            return false;
        }
        $comp = version_compare($targetVersion, static::$version);
        return ($comp == -1);
    }
}
