<?php

namespace Tests\TestDummy;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Laracasts\TestDummy\DynamicAttributeReplacer as BaseReplacer;

class DynamicAttributeReplacer extends BaseReplacer
{
    /**
     * Perform dynamic replacements of text
     */
    function __construct()
    {
        $this->fake = Faker::create();

        $this->addFakerProviders();
    }

    protected function addFakerProviders()
    {
        $fake = $this->fake;

        $fake->addProvider(new FakerProviderHdfcGateway($fake));

        $fake->addProvider(new FakerProviderFrequent($fake));
    }

    /**
     * Update any placeholders with dynamic fake substitutes.
     *
     * @param $value
     * @return mixed
     */
    protected function updateColumnValue($value)
    {
        return preg_replace_callback('/\$([a-z]+)/', function($matches)
        {
            if ($this->isASupportedFakeType($fakeType = $matches[1]))
            {
                return call_user_func([$this, 'getFake' . ucwords($fakeType)]);
            }

            //
            // If we don't support a method, then faker should.
            // The parent class silently returned without throwing error
            // but I think we should throw error rather than failing silently.
            //

            return $this->fake->$fakeType;

            // // If we don't recognize it, we'll just keep it as it is.
            // return $matches[0];
        }, $value);
    }
}
