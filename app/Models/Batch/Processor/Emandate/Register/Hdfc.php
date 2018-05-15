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

    const SUCCESS   = 'success';
    const REJECT    = 'reject';

    protected $gatewayPaymentMapping = [
        self::TOKEN_ID       => NetbankingEntity::SI_TOKEN,
        self::TOKEN_STATUS   => NetbankingEntity::SI_STATUS,
        self::ERROR_MESSAGE  => NetbankingEntity::SI_MSG,
        self::ACCOUNT_NUMBER => NetbankingEntity::ACCOUNT_NUMBER,
    ];

    protected function getDataFromRow(array & $entry): array
    {
        $tokenId = $entry[Batch\Header::HDFC_EM_REGISTER_MANDATE_ID];

        $gatewayTokenStatus = $entry[Batch\Header::HDFC_EM_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus);

        $accountNumber = $entry[Batch\Header::HDFC_EM_REGISTER_ACCOUNT_NUMBER];

        return [
            self::GATEWAY_TOKEN  => $tokenId,
            self::TOKEN_STATUS   => $status,
            self::ACCOUNT_NUMBER => $accountNumber,
            self::ERROR_MESSAGE  => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::TOKEN_ID       => $tokenId,
        ];
    }

    /**
     * @param string $gatewayTokenStatus
     * @return string
     */
    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        if (Netbanking\Hdfc\Status::isRegistrationSuccess($gatewayTokenStatus) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }

        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        if ($this->getTokenStatus($gatewayTokenStatus) === Token\RecurringStatus::CONFIRMED)
        {
            return null;
        }
        else
        {
            return $entry[Batch\Header::HDFC_EM_REGISTER_REMARK] ?? 'FAILED';
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($payment['id'], Action::AUTHORIZE);
    }
}
