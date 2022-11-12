<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\RecurringType;
use RZP\Models\Merchant\RazorxTreatment;

class Sbi extends Base
{
    protected $gateway = Gateway::NETBANKING_SBI;

    protected $useSpreadSheetLibrary = false;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        if(isset($row["Mandate Holder's Account No"]) === true)
        {
            $row["Mandate Holder’s Account No"] = $row["Mandate Holder's Account No"];
        }

        return [
            self::PAYMENT_ID            => $row[ Batch\Header::SBI_EM_DEBIT_CUSTOMER_REF_NO ],
            self::ACCOUNT_NUMBER        => $row[ Batch\Header::SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER],
            self::GATEWAY_ERROR_MESSAGE => $row[ Batch\Header::SBI_EM_DEBIT_REASON],
            self::GATEWAY_RESPONSE_CODE => $row[ Batch\Header::SBI_EM_DEBIT_DEBIT_STATUS],
            self::AMOUNT                => $row[ Batch\Header::SBI_EM_DEBIT_AMOUNT],
        ];
    }

    protected function getGatewayAttributes(array $content): array
    {
        return [
            Netbanking\Base\Entity::RECEIVED       => true,
            Netbanking\Base\Entity::ERROR_MESSAGE  => $content[self::GATEWAY_ERROR_MESSAGE],
            Netbanking\Base\Entity::STATUS         => $content[self::GATEWAY_RESPONSE_CODE],
        ];
    }

    protected function isAuthorized(array $content): bool
    {
        return Netbanking\Sbi\Emandate\Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE]);
    }

    protected function getApiErrorCode(array $content): string
    {
        $errorDescription = $content[self::GATEWAY_ERROR_MESSAGE];

        return Netbanking\Sbi\Emandate\ErrorCode::getDebitErrorCode($errorDescription);
    }

    protected function getNumRowsToSkipExcelFile()
    {
        return 5;
    }

    protected function getStartRowExcelFiles()
    {
        return 6;
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        // Get payment
        $payment = $this->repo->payment->findOrFail($paymentId);

        $token = $payment->getGlobalOrLocalTokenEntity();

        $tokenAccNo = ltrim($token->getAccountNumber(), '0');

        $fileAccountNumber = ltrim($content[self::ACCOUNT_NUMBER], '0');

        if (($payment->getGateway() !== $this->gateway) or
            ($payment->getRecurringType() !== RecurringType::AUTO) or
            ($token === null) or
            ($tokenAccNo !== $fileAccountNumber))
        {
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_RECURRING_PAYMENT_NOT_FOUND,
                null,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'token_id' => $token->getId(),
                    'gateway' => 'netbanking_sbi'
                ]);
        }

        return $payment;
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Batch\Header::SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER]);
    }

    public function shouldSendToBatchService(): bool
    {
        $key = Carbon::now()->getTimestamp();

        $razorxTreatment = RazorxTreatment::BATCH_SERVICE_EMANDATE_DEBIT_SBI_MIGRATION;

        $variant = $this->app->razorx->getTreatment($key,
            $razorxTreatment,
            $this->mode
        );

        return (strtolower($variant) === 'on');
    }

    protected function getBankStatus($status): string
    {
        return Netbanking\Sbi\Emandate\Status::bankMappedStatus($status);
    }
}
