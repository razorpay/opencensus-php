<?php

namespace RZP\Models\Batch\Processor\Nach\Register;

use RZP\Models\Batch;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Enach\Citi;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;

class NachCiti extends Base
{
    protected $gateway = Gateway::NACH_CITI;

    protected function getDataFromRow(array $entry): array
    {
        $paymentId = $entry[Batch\Header::CITI_NACH_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO];

        $gatewayTokenStatus = $entry[Batch\Header::CITI_NACH_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus, $entry);

        $umrn = $entry[Batch\Header::CITI_NACH_REGISTER_UMRN];

        return [
            self::GATEWAY_TOKEN    => $umrn,
            self::TOKEN_STATUS     => $status,
            self::TOKEN_ERROR_CODE => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID       => $paymentId,
        ];
    }

    /**
     * @param string $gatewayTokenStatus
     * @return string
     */
    protected function getTokenStatus(string $gatewayTokenStatus, array $content): string
    {
        if (Citi\Status::isRegistrationAcknowledged($gatewayTokenStatus, $content) === true)
        {
            return Token\RecurringStatus::INITIATED;
        }
        elseif (Citi\Status::isRegistrationSuccess($gatewayTokenStatus, $content) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }
        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        $status = $this->getTokenStatus($gatewayTokenStatus, $entry) ;

        if ($status === Token\RecurringStatus::CONFIRMED || $status === Token\RecurringStatus::INITIATED)
        {
            return null;
        }
        else
        {
            return $entry[Batch\Header::CITI_NACH_REGISTER_REMARKS] ?? 'FAILED';
        }
    }
}
