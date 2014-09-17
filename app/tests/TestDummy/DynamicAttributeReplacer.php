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
        if (is_array($value))
            return $value;

        $matches = array();

        $isMatch = preg_match('/\$([a-zA-Z]+)/', $value, $matches);

        $ret = $value;

        if ($isMatch)
        {
            if ($this->isASupportedFakeType($fakeType = $matches[1]))
            {
                $ret = call_user_func([$this, 'getFake' . ucwords($fakeType)]);
            }
            else
            {
                //
                // If we don't support a method, then faker should.
                // The parent class silently returned without throwing error
                // but I think we should throw error rather than failing silently.
                //

                try
                {
                    $ret = $this->fake->$fakeType;
                }
                catch (\InvalidArgumentException $e)
                {
                    ;
                }
            }
        }

        // If $value is non-scalar like array, then just return it.
        // Otherwise replace it with the matched part
        // eg: ab$integer should become ab1234

        if (is_scalar($ret))
        {
            preg_replace('/\$([a-z]+)/', $value, $ret);
        }

        return $ret;
    }
}
