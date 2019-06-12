<?php

namespace RZP\Models\BankingAccount;

class RblWebhook extends Rbl
{
    public function preProcessAccountInfoNotification(array $input)
    {
        $input = $input[RblFields::RZP_ALERT_NOTIFICATION_REQUEST][RblFields::BODY];

        (new Validator)->validateInput('rbl_account_info_notification', $input);

        // ToDo fix this field name based on update from RBL
        $bankReferenceNumber = $input[RblFields::REF_NUM_1];

        return $bankReferenceNumber;
    }

    public function processAccountInfoNotification(array $input)
    {
        $input = $input[RblFields::RZP_ALERT_NOTIFICATION_REQUEST][RblFields::BODY];

        $attributesToSave = $this->getMappedAttributes(RblFields::$rblFieldsToEntityMap, $input);
s($attributesToSave);
        $attributesToSave[Entity::ACCOUNT_ACTIVATION_DATE] = $this->parseAndFormatRblDate(
                                                                    $attributesToSave[Entity::ACCOUNT_ACTIVATION_DATE]);

        $attributesToSave[Entity::STATUS] = Status::PROCESSED;

        $attributesToSave[Entity::BANK_INTERNAL_STATUS] = RblStatus::CLOSED;

        return $attributesToSave;
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string  $status)
    {
        $tranId = $input[RblFields::RZP_ALERT_NOTIFICATION_REQUEST][RblFields::HEADER][RblFields::TRAN_ID];

        $response = [
            RblFields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                RblFields::HEADER =>
                    [
                        RblFields::TRAN_ID => $tranId,
                    ],
                RblFields::BODY =>
                    [
                        RblFields::STATUS => $status
                    ]
            ],
        ];

        return $response;

    }
}