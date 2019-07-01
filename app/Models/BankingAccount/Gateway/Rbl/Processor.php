<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;

class Processor extends BankingAccount\Gateway\Processor
{
    const DATE_FORMAT = 'Y-m-d';

    const PINCODES_REDIS_KEY = 'rbl_pincode_set';

    public function preProcessAccountInfoNotification(array $input)
    {
        (new Validator)->validateInput(Validator::PRE_ACCOUNT_INFO_WEBHOOK, $input);

        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        (new Validator)->validateInput(Validator::ACCOUNT_INFO_WEBHOOK, $input);
    }

    public function processAccountInfoNotification(array $input): array
    {
        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $this->parseAndFormatRblDate(
                                                        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE]);

        $attributes[BankingAccount\Entity::STATUS] = BankingAccount\Status::PROCESSED;

        $attributes[BankingAccount\Entity::BANK_INTERNAL_STATUS] = Status::CLOSED;

        $attributes[BankingAccount\Entity::BANK_REFERENCE_NUMBER] = $input[Fields::REF_NUM_1];

        return $attributes;
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        $tranId = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::HEADER][Fields::TRAN_ID] ?? null;

        $bankStatus = Status::getInternalStatusForBankWebhook($status);

        if (empty($tranId) === true)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_WEBHOOK_MISSING_TRANSACTION_ID,
                [
                    'channel' => BankingAccount\Channel::RBL,
                    'input'   => $input,
                ]);

            $bankStatus = Status::FAILURE;
        }

        $response = [
            Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                Fields::HEADER =>
                    [
                        Fields::TRAN_ID => $tranId,
                    ],
                Fields::BODY   =>
                    [
                        Fields::STATUS => $bankStatus
                    ]
            ],
        ];

        return $response;
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        $keys = [
            BankingAccount\Entity::BANK_INTERNAL_STATUS,
            BankingAccount\Entity::STATUS,
            BankingAccount\Entity::BANK_INTERNAL_REFERENCE_NUMBER,
            BankingAccount\Entity::BANK_REFERENCE_NUMBER,
        ];

        $attributes = array_only($input, $keys);

        (new Validator)->validateInput(Validator::ACCOUNT_UPDATE, $attributes);

        $this->checkRblToInternalStatusMapping($attributes);
    }

    protected function validateInputForAccountCreation(array $input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_AVAILABILITY, $input);
    }

    protected function preProcessInputForAccountCreation(array $input)
    {
        return $this->isPincodeRblServiceable($input[BankingAccount\Entity::PINCODE]);
    }

    /**
     * We are not rejecting requests based on the pincode availability for now.
     * This is being done to store all the leads we get for account creation.
     * Later we can choose to reject requests directly from here.
     *
     * @param string $pincode
     *
     * @return array
     */
    protected function isPincodeRblServiceable(string $pincode)
    {
        $availability = parent::isPincodeServiceable($pincode);

        return [
            BankingAccount\Entity::STATUS => BankingAccount\Status::CREATED
        ];
    }

    protected function getMappedAttributes($map, array $input)
    {
        $attr = [];

        foreach ($input as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey        = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function parseAndFormatRblDate(string $date)
    {
        $date = Carbon::parse($date)->format(self::DATE_FORMAT);

        return $date;
    }

    protected function checkRblToInternalStatusMapping(array $input)
    {
        if (isset($input[BankingAccount\Entity::BANK_INTERNAL_STATUS]) === false)
        {
            return;
        }

        $bankInternalStatus = $input[BankingAccount\Entity::BANK_INTERNAL_STATUS];

        $status = $input[BankingAccount\Entity::STATUS];

        Status::validateInternalBankStatusMappingToStatus($bankInternalStatus, $status);
    }
}
