<?php

namespace RZP\Models\Payment\PaymentSupportingDocuments;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createPaymentSupportingDocuments(array $input): Entity
    {
        $paymentSupportingDoc = new Entity;

        $paymentSupportingDoc->generateId();

        $paymentSupportingDoc->build($input);

        $this->repo->payment_supporting_documents->saveOrFail($paymentSupportingDoc);

        $this->trace->info(TraceCode::PAYMENT_SUPPORTING_DOCUMENTS_SAVE_SUCCESS, [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'payment_id'              => $input[Entity::PAYMENT_ID],
            'id'                      => $paymentSupportingDoc->getId()
        ]);

        return $paymentSupportingDoc;
    }
}
