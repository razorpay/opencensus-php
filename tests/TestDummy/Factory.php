<?php

namespace RZP\Tests\TestDummy;

use Laracasts\TestDummy as Base;

class Factory extends Base\Factory
{
    /**
     * The path to the factories directory.
     *
     * @var string
     */
    public static $factoriesPath = 'tests/Functional/Fixtures/Factory';

    /**
     * The user registered factories.
     *
     * @var array
     */
    protected static $factories;

    /**
     * Create a new factory instance.
     *
     * @param string        $factoriesPath
     * @param IsPersistable $databaseProvider
     */
    public function __construct($factoriesPath = null, IsPersistable $databaseProvider = null)
    {
        $this->loadFactories($factoriesPath);
        $this->setDatabaseProvider($databaseProvider);
    }

    /**
     * Create a new Builder instance.
     *
     * @return Builder
     */
    public function getBuilder()
    {
        return new Base\Builder($this->databaseProvider(), $this->factories());
    }

    /**
     * Load the user provided factories.
     *
     * @param  string $factoriesPath
     * @return void
     */
    private function loadFactories($factoriesPath)
    {
        $factoriesPath = $factoriesPath ?: static::$factoriesPath;

        if ( ! static::$factories) {
            static::$factories = (new FactoriesLoader)->load($factoriesPath);
        }
    }

    /**
     * Set the database provider for the data generation.
     *
     * @param  IsPersistable $provider
     * @return void
     */
    protected function setDatabaseProvider($provider = null)
    {
        if ( ! static::$databaseProvider) {
            static::$databaseProvider = $provider ?: new Base\EloquentModel;
        }
    }
}
