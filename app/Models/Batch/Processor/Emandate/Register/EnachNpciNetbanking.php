<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use Config;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity;
use RZP\Gateway\Enach\Npci\Netbanking;
use Razorpay\Spine\Exception\DbQueryException;

class EnachNpciNetbanking extends Base
{
    const GATEWAY = Gateway::ENACH_NPCI_NETBANKING;

    const UMRN        = 'umrn';
    const NPCI_REF_ID = 'npci_reference_id';
    const MESSAGE_ID  = 'message_id';

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
            self::MESSAGE_ID                  => $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_MESSAGE_ID],
            self::NPCI_REF_ID                 => $entry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_REQID],
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

    // commenting out the below function as NPCI has requested to do recon based on their ref id.
    // keeping this change as NPCI is planning to change this to based on our payment id in the future
    /*protected function forceAuthorizeIfApplicable(Payment\Entity $payment, array $data)
    {
        $authorizeSuccess = true;

        if (($payment->isFailed() === true) and
            ($data[self::TOKEN_STATUS] === Token\RecurringStatus::CONFIRMED))
        {
            $paymentService = new Payment\Service;

            $paymentId = $payment->getPublicId();

            $this->trace->critical(TraceCode::FORCE_AUTH_FAILED_PAYMENT,
                [
                    'status'     => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            $response = $paymentService->forceAuthorizeFailed($paymentId, []);

            $this->trace->info(
                TraceCode::EMANDATE_RECON_FORCE_AUTH_RESPONSE,
                [
                    'info_code' => 'FORCE_AUTHORIZATION_RESPONSE',
                    'message'   => 'Response received from force authorization',
                    'response'  => $response
                ]
            );

            $payment->reload();

            $authorizeSuccess = $payment->isAuthorized();
        }

        return [$payment, $authorizeSuccess];
    }*/

    protected function fetchPaymentEntity($data): Payment\Entity
    {
        try
        {
            $enach = $this->repo->enach->findByGatewayReferenceIdAndStatus(
                $data[self::NPCI_REF_ID],
                Netbanking\RegistrationStatus::SUCCESS
            );
        }
        catch (DBQueryException $ex)
        {
            $this->trace->traceException($ex);

            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_RECURRING_PAYMENT_NOT_FOUND,
                null,
                null,
                [
                    'npci_reference_number' => $data[self::NPCI_REF_ID],
                ]);
        }

        return $enach['payment'];
    }

    protected function shouldUpdateBatchOutputWithPaymentId()
    {
        return true;
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_AC_NO]);
    }
}
