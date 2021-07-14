<?php

namespace RZP\Tests\TestDummy;

use Laracasts\TestDummy as Base;
use RZP\Tests\Functional\Fixtures\Factory\FactoryData;

class FactoriesLoader extends Base\FactoriesLoader
{

    /**
     * Load the factories.
     *
     * @param  string $basePath
     * @return array
     */
    public function load($basePath)
    {
        $this->assertThatFactoriesDirectoryExists($basePath);

        $designer = new Base\Designer;
        $faker = new FakerAdapter;

        $factory = function ($name, $shortName, $attributes = []) use ($designer, $faker) {
            return $designer->define($name, $shortName, $attributes);
        };

        // In case we loaded data directly from an included file in the
        // $basePath directory
        //
        // foreach ((new Base\FactoriesFinder($basePath))->find() as $file) {
        //     include($file);
        // }

        FactoryData::defineEntityFactories($factory, $faker);

        return $designer->definitions();
    }

    /**
     * Assert that the given factories directory exists.
     *
     * @param  string $basePath
     * @return mixed
     * @throws TestDummyException
     */
    private function assertThatFactoriesDirectoryExists($basePath)
    {
        if ( ! is_dir(base_path($basePath))) {
            throw new Base\TestDummyException(
                "The path provided for the factories directory, {$basePath}, does not exist."
            );
        }
    }
}
