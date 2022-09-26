<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Models\Base;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{
    public function uploadMaxPaymentLimitViaFile(array $input): array
    {

        $paymentLimitEntity = (new Entity())->generateId();

        $entityId = $paymentLimitEntity->getId();

        $file = $input[Constants::FILE];

        (new File())->saveLocalFile($file, $paymentLimitEntity);

        $data = (new File())->getFileData($file);

        $headers = array_shift($data);

        $this->trace->info(TraceCode::MERCHANT_MAX_PAYMENT_LIMIT_CHECK_DATA, [
            'file' => $file,
            'data' => $data,
            'header' => $headers,
            'entity_id' => $entityId,
        ]);

        $response = (new Processor($paymentLimitEntity))->process($data, $headers, $entityId);

        $response["entity_id"] = $entityId;

        return $response;
    }


    public function executeMaxPaymentLimitWorkflow(array $input): array
    {
        return $this->core()->executeMaxPaymentLimitWorkflow($input);

    }

}
