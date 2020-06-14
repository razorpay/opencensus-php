<?php

namespace RZP\lib;

use FuzzyWuzzy\Fuzz;

use RZP\Exception;
use RZP\Error\ErrorCode;

class FuzzyMatcher
{
    const TITLES = ['mr', 'miss', 'mrs', 'master'];

    protected $expectedMatchPercent;

    protected $matchType;

    const JUMBLED_MATCH = 'jumbledMatch';

    const SIMPLE_MATCH = 'simpleMatch';

    const TOKEN_OR_TOKEN_SET_MATCH = 'tokenOrTokenSetMatch';

    const MATCH_TYPES = [self::JUMBLED_MATCH, self::SIMPLE_MATCH, self::TOKEN_OR_TOKEN_SET_MATCH];

    const CENT = 100;


    /**
     * FuzzyMatcher constructor.
     *
     * @param $expectedMatchPercent
     * @param $matchType
     *
     * @throws Exception\BadRequestException
     */
    public function __construct(float $expectedMatchPercent, string $matchType)
    {
        $this->validateMatchPercent($expectedMatchPercent);

        $this->validateMatchType($matchType);

        $this->matchType = $matchType;

        $this->expectedMatchPercent = $expectedMatchPercent;
    }

    /**
     * @param      $first
     * @param      $second
     * @param null $percentage
     *
     * @return bool
     */
    public function isMatch($first, $second, &$percentage = null): bool
    {
        $percentage = $this->matchPercent($first, $second);

        return ($percentage >= $this->expectedMatchPercent);
    }

    /**
     * @return string
     */
    public function getMatchType(): string
    {
        return $this->matchType;
    }

    /**
     * @param $first
     * @param $second
     *
     * @return float
     */
    protected function matchPercent($first, $second): float
    {
        $matchPercent = null;

        $first = $this->stripTitle($first);

        $second = $this->stripTitle($second);

        switch ($this->matchType)
        {
            case self::SIMPLE_MATCH:

                $matchPercent = $this->simpleFuzzyMatch($first, $second);

                break;

            case self::JUMBLED_MATCH:

                $matchPercent = $this->jumbledFuzzyMatch($first, $second);

                break;

            case self::TOKEN_OR_TOKEN_SET_MATCH:

                $matchPercent = $this->tokenOrTokenSetMatch($first, $second);

                break;

        }

        return $matchPercent;
    }

    /**
     *
     * @param $first
     * @param $second
     *
     * @return float
     */
    protected function tokenOrTokenSetMatch($first, $second): float
    {
        $first = strtolower(preg_replace('/\s+/', ' ', $first));

        $second = strtolower(preg_replace('/\s+/', ' ', $second));

        $fuzz = new Fuzz();

        $percentageFromRatio = $fuzz->ratio($first, $second);

        $percentageFromTokenSet = $fuzz->tokenSetRatio($first, $second);

        return max($percentageFromRatio, $percentageFromTokenSet);
    }

    /**
     * @param $first
     * @param $second
     *
     * @return float
     */
    protected function simpleFuzzyMatch($first, $second): float
    {
        $first = strtolower(preg_replace('/\s+/', '', $first));

        $second = strtolower(preg_replace('/\s+/', '', $second));

        $percent = 0;

        similar_text($first, $second, $percent);

        return $percent;
    }

    /**
     * @param $first
     * @param $second
     *
     * @return float
     */
    protected function jumbledFuzzyMatch($first, $second): float
    {
        $simpleMatchPercent = $this->simpleFuzzyMatch($first, $second);

        if($simpleMatchPercent >= $this->expectedMatchPercent)
        {
            return $simpleMatchPercent;
        }
        else
        {
            if($this->isJumbled($first, $second))
            {
                return self::CENT;
            }
            else
            {
                return $simpleMatchPercent;
            }
        }
    }

    /**
     * @param $first
     * @param $second
     *
     * @return bool
     */
    private function isJumbled($first, $second): bool
    {
        $firstWords = $this->getWords($first);

        $secondWords = $this->getWords($second);

        $firstWords = array_map('strtolower', $firstWords);

        $secondWords = array_map('strtolower', $secondWords);

        sort($firstWords);

        sort($secondWords);

        return (implode($firstWords) === implode($secondWords));
    }

    /**
     * @param $expectedMatchPercent
     *
     * @throws Exception\BadRequestException
     */
    private function validateMatchPercent($expectedMatchPercent)
    {
        if ((is_float($expectedMatchPercent) === false)
            or ($expectedMatchPercent < 1)
            or ($expectedMatchPercent > 100))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_MATCH_PERCENT,
                null,
                ['$expectedMatchPercent' => $expectedMatchPercent]);
        }
    }

    /**
     * @param $matchType
     *
     * @throws Exception\BadRequestException
     */
    private function validateMatchType($matchType)
    {
        if (in_array($matchType, self::MATCH_TYPES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_MATCH_TYPE,
                null,
                ['$matchType' => $matchType]);
        }
    }

    private function stripTitle(string $inputString): string
    {
        $words = $this->getWords($inputString);

        if (in_array($words[0], self::TITLES, true) === true)
        {
            array_shift($words);
        }

        return (implode(" ", $words));
    }

    private function getWords(string $inputString): array
    {
        $inputString = preg_replace('!\s+!', ' ', $inputString);

        $words = explode(' ', $inputString);

        return $words;
    }

}
