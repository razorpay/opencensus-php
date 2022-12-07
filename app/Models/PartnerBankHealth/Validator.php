<?php

namespace RZP\Models\PartnerBankHealth;

use Razorpay\IFSC;

use RZP\Base;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const PARTNER_BANK_HEALTH = 'partner_bank_health';
    const NOTIFICATION        = 'notification';

    protected static $partnerBankHealthRules = [
        Constants::BEGIN                         => 'required|epoch',
        Constants::INSTRUMENT                    => 'required|array|size:2|custom',
        Constants::INCLUDE_MERCHANTS             => 'required|array|custom',
        Constants::EXCLUDE_MERCHANTS             => 'present|array',
        Constants::SOURCE                        => 'required|string|custom',
        Constants::MODE                          => 'required|string|custom',
        Constants::STATUS                        => 'required|string|custom',
        Constants::EXCLUDE_MERCHANTS . '.' . '*' => 'required|string|size:14',
    ];

    protected static $notificationRules = [
        Constants::SOURCE            => 'required|string|in:fail_fast,downtime',
        Constants::MODE              => 'required|string|custom',
        Constants::STATUS            => 'required|string|custom',
        Constants::INSTRUMENT        => 'required|array|size:2|custom',
        Constants::START_TIME        => 'sometimes|epoch',
        Constants::END_TIME          => 'sometimes|required_without:start_time|epoch',
        Constants::INCLUDE_MERCHANTS => 'required|array',
        Constants::EXCLUDE_MERCHANTS => 'present|array',
    ];

    protected static $notificationValidators = [
        'IncludeExcludeMerchants'
    ];

    public function validateEventType($attribute, $value)
    {
        list($source, $integrationType, $mode, $bankCode) = explode('.', $value);

        self::validateSource(Constants::SOURCE, $source);

        self::validateIntegrationType(Constants::INTEGRATION_TYPE, $integrationType);

        self::validateMode(Constants::MODE, $mode);

        /* Validate bank code only for direct integration */
        if (strpos($value, AccountType::DIRECT) !== false)
        {
            if (empty($bankCode) === true)
            {
                throw new BadRequestValidationFailureException("Invalid event type $value",
                                                               null,
                                                               [
                                                                   'integration_type' => $integrationType
                                                               ]);
            }

            $this->validateBankCode(Constants::BANK, strtoupper($bankCode));
        }
    }

    public static function validateMode($attribute, $value)
    {
        if ((empty($value) === true) or
            (Mode::isValid(strtoupper($value)) === false))
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }
    }

    protected function validateStatus($attribute, $value)
    {
        if (in_array($value, Status::getAllowedStatuses()) === false)
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }
    }

    protected function validateInstrument($attribute, $value)
    {
        $partnerBankIfsc = $value[Constants::BANK];

        $this->validateBankCode(Constants::BANK, $partnerBankIfsc);

        $integrationType = $value[Constants::INTEGRATION_TYPE];

        $this->validateIntegrationType(Constants::INTEGRATION_TYPE, $integrationType);
    }

    public static function validateBankCode($attribute, $value)
    {
        if (IFSC\IFSC::validateBankCode($value) === false)
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }
    }

    public static function validateSource($attribute, $value)
    {
        if (Events::isEventValid($value) === false)
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }
    }

    public static function validateIntegrationType($attribute, $value)
    {
        if ((empty($value) === true) or
            (($value !== AccountType::DIRECT) and
            ($value !== AccountType::SHARED)))
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }
    }

    /*
     *  $value is an array with keys as bank ifsc codes and one key being "affected_merchants"
     *  For bank ifsc as keys, the value would be an array having last_down_at and last_up_at keys
     *  These last_up_at and last_down_at timestamps will be updated with each incoming webhook from FTS
     */
    protected function validateValue($attribute, $value)
    {
        if (array_key_exists(Constants::AFFECTED_MERCHANTS, $value) === false)
        {
            throw new BadRequestValidationFailureException("$attribute is required");
        }

        foreach ($value as $idx => $content)
        {
            if ($idx === Constants::AFFECTED_MERCHANTS)
            {
                $this->validateAffectedMerchantsList($content);
                continue;
            }

            $this->validateBankCode(Constants::BANK, $idx);

            $lastDownAT = $content[Entity::LAST_DOWN_AT] ?? null;
            $lastUpAt = $content[Entity::LAST_UP_AT] ?? null;

            if((empty($lastDownAT) === true) and (empty($lastUpAt) === true))
            {
                throw new BadRequestValidationFailureException(
                    "Both last_up_at and last_down_at cannot be empty",
                    null,
                    [
                        $idx => $content
                    ]
                );
            }
        }
    }

    // Affected merchants list can either be ["ALL"] or [] or an array of merchant_ids.
    public function validateAffectedMerchantsList($affectedMerchantsList)
    {
        if (in_array('ALL', $affectedMerchantsList) === true)
        {
            if (count($affectedMerchantsList) > 1)
            {
                throw new BadRequestValidationFailureException("Invalid affected_merchants list");
            }

            return;
        }

        foreach ($affectedMerchantsList as $mid)
        {
            if ((is_string($mid) === false) or (strlen($mid) !== 14))
            {
                throw new BadRequestValidationFailureException("Invalid affected_merchants list");
            }
        }
    }

    protected function validateIncludeMerchants($attribute, $value)
    {
        if (empty($value))
        {
            throw new BadRequestValidationFailureException("Empty $attribute list");
        }

        if (array_first($value) === 'ALL')
        {
            if (count($value) !== 1)
            {
                throw new BadRequestValidationFailureException("Invalid $attribute list");
            }

            return;
        }

        foreach ($value as $mid)
        {
            \RZP\Models\Merchant\Entity::verifyUniqueId($mid);
        }
    }
}
