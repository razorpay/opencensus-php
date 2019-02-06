<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity;
use RZP\Gateway\Enach\Npci\Netbanking;

class EnachNpciNetbanking extends Base
{
    const GATEWAY = Gateway::ENACH_NPCI_NETBANKING;

    const UMRN = 'umrn';

    protected $gatewayPaymentMapping = [
        self::GATEWAY_REGISTRATION_STATUS => Entity::REGISTRATION_STATUS,
        self::GATEWAY_ERROR_CODE          => Entity::ERROR_CODE,
        self::GATEWAY_ERROR_DESCRIPTION   => Entity::ERROR_MESSAGE,
    ];

    protected function getDataFromRow(array $entry): array
    {
        $gatewayToken = $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_UMRN];

        $gatewayTokenStatus = $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_STATUS];

        $status = $this->getTokenStatus($gatewayTokenStatus, $entry);

        return [
            self::GATEWAY_TOKEN               => $gatewayToken,
            self::UMRN                        => $gatewayToken,
            self::TOKEN_STATUS                => $status,
            self::TOKEN_ERROR_CODE            => $this->getTokenErrorMessage($gatewayTokenStatus, $entry),
            self::PAYMENT_ID                  => $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_PAYMENT_ID],
            self::GATEWAY_REGISTRATION_STATUS => $gatewayTokenStatus,
            self::GATEWAY_ERROR_CODE          => $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_STATUS_CODE],
            self::GATEWAY_ERROR_DESCRIPTION   => $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_REASON],
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus, array $content): string
    {
        if (Netbanking\RegistrationStatus::isFileRegistrationSuccess($gatewayTokenStatus, $content) === true)
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
            return Netbanking\ErrorCodes\FileBasedErrorCodes::getRegistrationPublicErrorCode($entry);
        }
    }

    protected function getGatewayPayment(Payment\Entity $payment)
    {
        return $this->repo
                    ->enach
                    ->findAuthorizedPaymentByPaymentId($payment->getId());
    }
}
