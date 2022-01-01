<?php

namespace RZP\Models\Dispute\DebitNote;

use Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail;

class Service extends Base\Service
{

    public function postBatch($input)
    {
        try
        {
            $this->trace->info(TraceCode::DEBIT_NOTE_BATCH_CREATE_INPUT, $input);

            $processedInput = $this->preProcessBatchInput($input);

            $entity = (new Core)->postDebitNote($processedInput);

            return $this->convertToBatchOutput($input, $entity);
        }
        catch (Exception $exception)
        {
            $this->trace->traceException($exception);

            return $this->convertToBatchOutputOnException($input, $exception);
        }
    }

    protected function preProcessBatchInput($input): array
    {
        $this->trace->info(TraceCode::DEBIT_NOTE_CREATE_INPUT, $input);

        (new Validator)->validateInput('batch', $input);

        $merchantId = $input[Constants::BATCH_MERCHANT_ID];

        (new Detail\Core)->getMerchantAndSetBasicAuth($merchantId);

        return [
            Entity::MERCHANT_ID              => $merchantId,
            Entity::ADMIN_ID                 => $this->app['request']->header(RequestHeader::X_Creator_Id, null),
            Constants::BATCH_SKIP_VALIDATION => $input[Constants::BATCH_SKIP_VALIDATION],
            Constants::PAYMENT_IDS           => explode(",", str_replace(' ', '', $input[Constants::BATCH_PAYMENT_IDS])),
        ];
    }

    protected function convertToBatchOutput(array $input, Entity $entity)
    {
        return [
            'input_payment_ids'   => $input[Constants::BATCH_PAYMENT_IDS],
            Entity::DEBIT_NOTE_ID => $entity->getId(),
            'error_description'   => '',
        ];
    }

    protected function convertToBatchOutputOnException($input, Exception $exception)
    {
        return [
            'input_payment_ids'   => $input[Constants::BATCH_PAYMENT_IDS],
            Entity::DEBIT_NOTE_ID => '',
            'error_description'   => $exception->getMessage(),
        ];
    }

}