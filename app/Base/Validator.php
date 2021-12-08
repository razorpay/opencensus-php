<?php

namespace RZP\Base;

use App;
use Lib\PhoneBook;
use libphonenumber\NumberParseException;
use Razorpay\Trace\Logger;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Contact\Entity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payout\BatchHelper;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected function throwExtraFieldsException($extraFields)
    {
        throw new Exception\ExtraFieldsException($extraFields);
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestValidationFailureException($messages);
    }

    public static function validateInputKeyExists(array $input, $key)
    {
        if (isset($input[$key]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $key . ' not given in the input');
        }
    }

    protected function isTestMode(): bool
    {
        return ($this->getMode() === Mode::TEST);
    }

    protected function isLiveMode(): bool
    {
        return ($this->getMode() === Mode::LIVE);
    }

    protected function getMode(): string
    {
        return App::getFacadeRoot()['rzp.mode'];
    }

    public function setStrictFalse()
    {
        $this->strict = false;

        return $this;
    }

    /**
     * @return Logger
     */
    protected function getTrace(): Logger
    {
        $app = App::getFacadeRoot();

        return $app['trace'];
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateBatchId($batchId)
    {
        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException(Entity::BATCH_ID . ' not present');
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validatePayoutId($payoutId)
    {
        if (empty($payoutId) === true)
        {
            throw new BadRequestValidationFailureException(Entity::ID . ' not present');
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateUpdateAction($payoutUpdateAction)
    {
        if (empty($payoutUpdateAction) === true)
        {
            throw new BadRequestValidationFailureException(BatchHelper::PAYOUT_UPDATE_ACTION . ' not present');
        }
    }

    public function validateBatchCreatorId($batchCreatorId)
    {
        if (empty($batchCreatorId) === true)
        {
            throw new BadRequestValidationFailureException(Entity::CREATOR_ID . ' not present');
        }
    }

    public function validateBatchCreatorType($batchCreatorType)
    {
        if (empty($batchCreatorType) === true)
        {
            throw new BadRequestValidationFailureException(Entity::CREATOR_TYPE . ' not present');
        }
    }

    /**
     * @param $idempotencyKey
     * @param $batchId
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateIdempotencyKey($idempotencyKey, $batchId)
    {
        if (empty($idempotencyKey) === true)
        {
            throw new BadRequestValidationFailureException(
                Entity::IDEMPOTENCY_KEY . ' not present',
                null,
                [
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    Entity::BATCH_ID        => $batchId,
                ]
            );
        }
    }
}
