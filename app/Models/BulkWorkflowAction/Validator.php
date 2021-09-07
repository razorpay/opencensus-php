<?php


namespace RZP\Models\BulkWorkflowAction;


use App;
use Hash;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    /**
     * Validates if risk attributes have valid values or not
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateActionRiskAttributes(array $input)
    {
        $this->validateAttributesNotNull($input);

        if(isset($input[Constants::CLEAR_RISK_TAGS]) === true)
        {
            $this->validateClearRiskTags($input[Constants::CLEAR_RISK_TAGS]);
        }
        else
        {
            $this->validateActionRiskReasons($input[Constants::RISK_REASONS]);

            $this->validateActionRiskSource($input[Constants::RISK_SOURCES]);

            $this->validateActionRiskTags($input[Constants::RISK_TAG]);
        }

        if(isset($input[Constants::TRIGGER_COMMUNICATION]) === true)
        {
            $this->validateActionTriggerCommunication($input[Constants::TRIGGER_COMMUNICATION]);
        }
    }

    /**
     * Validates all the parameters required for risk attributes are present.
     * Two flows are there
     * 1. Constructive
     * 2. Destructive
     * @param $input
     * @throws BadRequestValidationFailureException
     */
    private function validateAttributesNotNull($input)
    {
        if (empty($input) === true or (
                // destructive flow attributes
                (isset($input[Constants::RISK_REASONS]) === false
                 or isset($input[Constants::RISK_TAG]) === false
                 or isset($input[Constants::RISK_SOURCES]) === false)
                // constructive flow attributes: unsuspend/release funds/re-enable live
                and isset($input[Constants::CLEAR_RISK_TAGS]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_ACTION_RISK_ATTRIBUTES_REQUIRED, $input);
        }
    }

    /**
     * Validates the value of clear risk tag key for risk attributes
     * Two flows are there
     * 1. Constructive
     * 2. Destructive
     * @param $input
     * @throws BadRequestValidationFailureException
     */
    private function validateClearRiskTags($value)
    {
        if ($value < 0 or $value > 1)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_ACTION_CLEAR_TAG_VALUE,$value);
        }
    }

    /**
     * validates the values of risk reasons provided.
     * @param $riskReasons
     * @throws BadRequestValidationFailureException
     */
    private function validateActionRiskReasons($riskReasons)
    {
        foreach ($riskReasons as $riskReason)
        {
            if (in_array($riskReason, Constants::RISK_REASON_LIST) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_INVALID_ACTION_RISK_REASON, $riskReason);
            }
        }
    }

    /**
     * validates the values of risk source provided.
     * @param $riskSources
     * @throws BadRequestValidationFailureException
     */
    private function validateActionRiskSource($riskSources)
    {
        foreach ($riskSources as $riskSource)
        {
            if (in_array($riskSource, Constants::RISK_SOURCE_LIST) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_INVALID_ACTION_RISK_SOURCE, $riskSource);
            }
        }
    }

    /**
     * validates the values of risk tag provided.
     * @param $riskTag
     * @throws BadRequestValidationFailureException
     */
    private function validateActionRiskTags($riskTag)
    {
        if (in_array($riskTag, Constants::RISK_TAG_LIST) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_INVALID_ACTION_RISK_TAG, $riskTag);
        }
    }

    /**
     * validates the values of TriggerCommunication provided.
     * @param $communications
     * @throws BadRequestValidationFailureException
     */
    private function validateActionTriggerCommunication($communications)
    {
        foreach ($communications as $communication)
        {

            if (in_array($communication, Constants::TRIGGER_COMMUNICATION_LIST) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_UNSUPPORTED_COMMUNICATION_TYPE, $communication);
            }
        }
    }
}
