<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;

trait PanVerifier
{
    use DefaultVerifier;

    /**
     * @var string
     */
    protected $panOwnerName;

    protected $nameFromNSDL;

    public function __construct(string $panOwnerName, array $data)
    {
        $this->initData($data);

        $this->panOwnerName = $panOwnerName;
        $this->nameFromNSDL = $this->data[Constants::PAN_NAME_FROM_NSDL] ?? null;
    }

    abstract function getExpectedMatchPercentage();

    protected function isCorrectDetails(): bool
    {
        return empty($this->nameFromNSDL) === false;
    }

    protected function isDetailsMatch(): bool
    {
        if ($this->isCorrectDetails() === false)
        {
            return false;
        }

        $poiFuzzyMatcher = new FuzzyMatcher($this->getExpectedMatchPercentage(), FuzzyMatcher::SIMPLE_MATCH);

        $isMatch = $poiFuzzyMatcher->isMatch($this->panOwnerName, $this->nameFromNSDL, $matchPercentage);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => $this->documentType,
                Constants::DETAILS_FROM_API_RESPONSE => $this->nameFromNSDL,
                Constants::DETAILS_FROM_USER         => $this->panOwnerName,
                Constants::MATCH_THRESHOLD           => $this->getExpectedMatchPercentage(),
                Constants::MATCH_PERCENTAGE          => $matchPercentage,
                Constants::SUCCESS                   => ($isMatch === true),
                Constants::MATCH_TYPE                => FuzzyMatcher::SIMPLE_MATCH,
            ]);

        return ($isMatch === true);
    }

}
