<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;

trait PanVerifier
{
    /**
     * @var string
     */
    protected $panOwnerName;

    protected $data;

    protected $nameFromNSDL;

    protected $internalErrorCode;

    protected $isSuccessResponse;

    public function __construct(string $panOwnerName, array $data)
    {
        $this->panOwnerName      = $panOwnerName;
        $this->data              = $data;
        $this->nameFromNSDL      = $this->data[Constants::PAN_NAME_FROM_NSDL] ?? null;
        $this->internalErrorCode = $this->data[Constants::INTERNAL_ERROR_CODE] ?? '';
        $this->isSuccessResponse = $this->data[Constants::SUCCESS] ?? false;
    }

    abstract function getIncorrectDetailsStatus();

    abstract function getFailedStatus();

    abstract function getNotMatchedStatus();

    abstract function getVerifiedStatus();

    abstract function getExpectedMatchPercentage();

    public function verify()
    {
        if ($this->isSuccessResponse === true)
        {
            return $this->getStatusForSuccess();
        }

        return $this->getStatusForFailure();
    }

    protected function isCorrectDetails(): bool
    {
        return empty($this->nameFromNSDL) === false;
    }

    protected function isNameMatch(): bool
    {
        if ($this->isCorrectDetails() === false)
        {
            return false;
        }
        $poiFuzzyMatcher = new FuzzyMatcher($this->getExpectedMatchPercentage(), FuzzyMatcher::SIMPLE_MATCH);

        return $poiFuzzyMatcher->isMatch($this->panOwnerName, $this->nameFromNSDL);
    }

    protected function getStatusForSuccess()
    {
        if ($this->isCorrectDetails() === false)
        {
            return $this->getIncorrectDetailsStatus();
        }

        if ($this->isNameMatch() === false)
        {
            return $this->getNotMatchedStatus();
        }

        return $this->getVerifiedStatus();
    }

    protected function getStatusForFailure()
    {
        switch ($this->internalErrorCode)
        {
            case  Constants::VALIDATION_ERROR :
            case  Constants::NO_DATA_FOUND:
            case  Constants::BAD_REQUEST:

                return $this->getIncorrectDetailsStatus();

                break;

            case  Constants::UNAUTHORIZED:

                return $this->getFailedStatus();
                break;

            default :
                return $this->getFailedStatus();
        }
    }

}
