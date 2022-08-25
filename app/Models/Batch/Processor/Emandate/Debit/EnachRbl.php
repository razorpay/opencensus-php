<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use Config;
use Carbon\Carbon;
use RZP\Gateway\Enach\Rbl;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Enach\Base\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as Headings;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;

    const UMRN = 'umrn';

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);


        return [
            self::PAYMENT_ID            => $row[Headings::REFNO],
            self::AMOUNT                => $row[Headings::AMOUNT],
            self::GATEWAY_RESPONSE_CODE => $row[Headings::STATUS],
            self::GATEWAY_ERROR_CODE    => $row[Headings::REASON_CODE] ?? null,
            self::GATEWAY_ERROR_MESSAGE => $row[Headings::REASON_DESCRIPTION] ?? null,
            self::UMRN                  => $row[Headings::UMRN],
        ];
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $umrn = $content[self::UMRN];

        $payment = $this->repo->payment->fetchDebitEnachPaymentPendingAuth(
                                                                $this->gateway,
                                                                $paymentId,
                                                                $umrn);

        return $payment;
    }

    protected function getGatewayPayment(string $paymentId)
    {
        return $this->repo
                    ->enach
                    ->findAuthorizedPaymentByPaymentId($paymentId);
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        return [
            Entity::STATUS        => $parsedData[self::GATEWAY_RESPONSE_CODE],
            Entity::ERROR_CODE    => $parsedData[self::GATEWAY_ERROR_CODE],
            Entity::ERROR_MESSAGE => $parsedData[self::GATEWAY_ERROR_MESSAGE],
        ];
    }

    protected function isAuthorized(array $content): bool
    {
        return Rbl\Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE], $content);
    }

    protected function isRejected(array $content): bool
    {
        return Rbl\Status::isDebitRejected($content[self::GATEWAY_RESPONSE_CODE], $content);
    }

    protected function getApiErrorCode(array $content): string
    {
        return Rbl\ErrorCodes::getDebitPublicErrorCode($content);
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Headings::BENEFICIARYACNO]);
    }

    protected function getBankStatus($status): string
    {
        return Rbl\Status::bankMappedStatus($status);
    }

    public function shouldSendToBatchService(): bool
    {
        $key = Carbon::now()->getTimestamp();

        $razorxTreatment = RazorxTreatment::BATCH_SERVICE_EMANDATE_DEBIT_ENACH_RBL_MIGRATION;

        $variant = $this->app->razorx->getTreatment($key,
            $razorxTreatment,
            $this->mode
        );

        $result = (strtolower($variant) === 'on');

        return $result;
    }
}
