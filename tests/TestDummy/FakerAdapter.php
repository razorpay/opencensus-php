<?php

namespace RZP\Tests\TestDummy;

use Laracasts\TestDummy as Base;
use Faker\Factory as Faker;

class FakerAdapter extends Base\FakerAdapter
{
    /**
     * Create a new FakerAdapter instance.
     *
     * @param mixed $generator
     */
    public function __construct($generator = null)
    {
        parent::__construct($generator);

        $faker = $this->createFakerInstance();

        $this->generator = $generator ?: $faker;
    }

    /**
     * Set the locale of the generator
     *
     * @param string $locale
     */
    // public function locale($locale)
    // {
    //     $this->generator = $this->createFakerInstance($locale);
    // }

    protected function createFakerInstance()
    {
        $faker = Faker::create();

        $faker->addProvider(new FakerProviderHdfcGateway($faker));

        $faker->addProvider(new FakerProviderFrequent($faker));

        return $faker;
    }
}
