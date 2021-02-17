<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

 use RZP\Models\Batch;
 use RZP\Models\Payment;
 use RZP\Gateway\Netbanking;
 use RZP\Gateway\Base\Action;
 use RZP\Models\Customer\Token;
 use RZP\Models\Payment\Gateway;
 use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Hdfc extends Base
{
    const GATEWAY   = Gateway::NETBANKING_HDFC;

    protected $gatewayPaymentMapping = [
        self::TOKEN_STATUS     => NetbankingEntity::SI_STATUS,
        self::TOKEN_ERROR_CODE => NetbankingEntity::SI_MSG,
    ];

    protected function getDataFromRow(array $entry): array
    {
        $paymentId = $entry[Batch\Header::HDFC_EM_REGISTER_MERCHANT_UNIQUE_REF_NO];

        $gatewayTokenStatus = $entry[Batch\Header::HDFC_EM_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus, $entry);

        return [
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
        if (Netbanking\Hdfc\Status::isRegistrationSuccess($gatewayTokenStatus, $content) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        if ($this->getTokenStatus($gatewayTokenStatus, $entry) === Token\RecurringStatus::CONFIRMED)
        {
            return null;
        }
        else
        {
            return $entry[Batch\Header::HDFC_EM_REGISTER_REMARKS] ?? 'FAILED';
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndActionOrFail($payment['id'], Action::AUTHORIZE);
    }


    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Batch\Header::HDFC_EM_REGISTER_ACCOUNT_NUMBER]);
    }
}
