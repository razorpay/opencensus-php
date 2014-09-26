<?php

namespace Tests\TestDummy;

use Laracasts\TestDummy\Factory as BaseFactory;

class Factory extends BaseFactory
{
    /**
     * Create a new Builder instance.
     *
     * @return Builder
     */
    protected static function getInstance()
    {
        if ( ! static::$fixtures) static::setFixtures();
        if ( ! static::$databaseProvider) static::setDatabaseProvider();

        return new Builder(static::$databaseProvider, static::$fixtures);
    }
}