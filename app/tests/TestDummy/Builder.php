<?php

namespace Tests\TestDummy;

use Laracasts\TestDummy\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Merge the fixture with any potential overrides.
     *
     * @param $type
     * @param $fields
     * @return array
     */
    protected function mergeFixtureWithOverrides($type, array $fields)
    {
        // First, we'll merge the default fixture will any
        // overrides that the caller provides.
        $data = array_merge($this->getFixture($type), $fields);

        // Next, we'll do any necessary dynamic replacements.
        return (new DynamicAttributeReplacer)->replace($data);
    }


}
