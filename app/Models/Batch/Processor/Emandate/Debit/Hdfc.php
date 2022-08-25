<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Base as NetbankingBase;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class Hdfc extends Base
{
    protected $gateway = Gateway::NETBANKING_HDFC;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            self::PAYMENT_ID            => $row[ Headings::TRANSACTION_REF_NO ],
            self::ACCOUNT_NUMBER        => $row[ Headings::ACCOUNT_NO ],
            self::GATEWAY_ERROR_MESSAGE => $row[ Headings::REJECTION_REMARKS ],
            self::GATEWAY_RESPONSE_CODE => $row[ Headings::STATUS ],
            self::AMOUNT                => $row[ Headings::AMOUNT],
        ];
    }

    /**
     * @param array $content
     * @return array
     * @throws Exception\GatewayErrorException
     */
    protected function getGatewayAttributes(array $content): array
    {
        $gatewayStatus = strtolower($content[self::GATEWAY_RESPONSE_CODE]);

        return [
            NetbankingBase\Entity::RECEIVED       => true,
            NetbankingBase\Entity::ERROR_MESSAGE  => $content[self::GATEWAY_ERROR_MESSAGE],
            NetbankingBase\Entity::STATUS         => $gatewayStatus,
        ];
    }

    /**
     * @param array $content
     * @return bool
     * @throws Exception\GatewayErrorException
     */
    protected function isAuthorized(array $content): bool
    {
        return Netbanking\Hdfc\Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE]);
    }

    protected function getApiErrorCode(array $content): string
    {
        $errorDescription = $content[self::GATEWAY_ERROR_MESSAGE];

        return Netbanking\Hdfc\ErrorCode::getApiErrorCode($errorDescription);
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Headings::ACCOUNT_NO]);
    }

    protected function getBankStatus($status): string
    {
        return Netbanking\Hdfc\Status::bankMappedStatus($status);
    }

    public function shouldSendToBatchService(): bool
    {
        $result = false;

        $batchType = '' . $this->batch->getType() . '_' . $this->batch->getSubType() . '_' . $this->batch->getGateway() . '';

        if ($this->app->batchService->isCompletelyMigratedBatchType($batchType) === true)
        {
            // not required to call razorx.
            return true;
        }

        if ($this->app->batchService->isMigratingBatchType($batchType) === true)
        {
            //
            // Get the RazorxTreatment based on batch Type:
            // BATCH_SERVICE_<BATCH_TYPE>_MIGRATION
            // Eg: for payment_link, RazorxTreatment will be batch_service_emandate_debit_hdfc_migration
            //
            $razorxTreatment = 'batch_service_' . $batchType . '_migration';

            $this->trace->info(TraceCode::RAZORX_REQUEST, ['feature' => $razorxTreatment]);

            $variant = $this->getVariant($razorxTreatment);

            $result = (strtolower($variant) === 'on');
        }

        return $result;
    }
}
