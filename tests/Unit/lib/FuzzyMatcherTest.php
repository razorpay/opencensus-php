<?php

namespace RZP\Tests\Unit\lib;

use RZP\lib\FuzzyMatcher;
use RZP\Tests\Functional\TestCase;


class FuzzyMatcherTest extends TestCase
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FuzzyMatcherTestData.php';

        parent::setUp();
    }

    public function testTokenOrTokenSetMatch()
    {

        $fuzzyMatcher = new FuzzyMatcher(100, FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);

        foreach ($this->testData[__FUNCTION__] as $input)
        {
            $fuzzyMatcher->isMatch($input['param1'], $input['param2'], $matchPercentage);
            $this->assertEquals($input['expected_ratio'], $matchPercentage);
        }

    }

}
