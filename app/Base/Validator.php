<?php

namespace RZP\Base;

use App;
use Lib\PhoneBook;
use libphonenumber\NumberParseException;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Contact\Entity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payout\BatchHelper;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    const AMOUNT_REGEX                          = '/[^0-9]/';
    const AMOUNT                                = 'amount';

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
     * This is a custom Validate that an attribute is an active URL,
     * first it will check for ipv4 and return true if found. if not, it will then check for ipv6.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     *
     * @return bool|\Exception|Exception
     */
    public function validateActiveUrl($attribute, $value)
    {
        if (is_string($value) === false)
        {
            return false;
        }

        $url = parse_url($value, PHP_URL_HOST);

        if (empty($url) === false)
        {
            try
            {
                $checkForIPV4 = count(dns_get_record($url, DNS_A));

                if ($checkForIPV4 > 0)
                {
                    return true;
                }

                $checkForIPV6 = count(dns_get_record($url, DNS_AAAA));

                if ($checkForIPV6 > 0)
                {
                    return true;
                }
            }
            catch (\Throwable $e)
            {
                $this->getTrace()->traceException($e, Logger::ERROR, TraceCode::ACTIVE_URL_VALIDATION_FAILURE_EXCEPTION);

                return false;
            }
        }
        throw new BadRequestValidationFailureException(
            'The ' . $attribute . ' is not a valid URL.'
        );
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

    /**
     * Used for Payout and FuncAccount Amount validation.
     * This is added where Laravel "integer" validation failed like 112.99999999999999
     * @param $input
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateAmountAsInteger($input)
    {
        if (isset($input[self::AMOUNT]) === true)
        {
            $amount = $input[self::AMOUNT];

            if (is_string($amount) === true)
            {
                if ((empty($amount) === true) or
                    (preg_match(self::AMOUNT_REGEX, $amount) !== 0))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        "The amount must be an integer.",
                        self::AMOUNT,
                        [
                            self::AMOUNT => $input[self::AMOUNT],
                        ]
                    );
                }
            }
            else
            {
                if (is_int($amount) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        "The amount must be an integer.",
                        self::AMOUNT,
                        [
                            self::AMOUNT => $input[self::AMOUNT],
                        ]
                    );
                }
            }
        }
    }
}
