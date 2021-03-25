<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository();
    }

    public function create(array $input, $requestPayload)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_SAVE_REQUEST,
            [
                Entity::QR_CODE_ID            => $input[Entity::QR_CODE_ID],
                Entity::TRANSACTION_REFERENCE => $input[Entity::TRANSACTION_REFERENCE]
            ]
        );

        $qrPaymentRequest = new Entity();

        $qrPaymentRequest->setRequestPayload($requestPayload);

        $qrPaymentRequest->findAndSetRequestSource();

        $qrPaymentRequest->build($input);

        $this->repo->saveOrFail($qrPaymentRequest);

        $this->trace->info(
            TraceCode::QR_PAYMENT_REQUEST_SAVED,
            [
                Entity::ID                    => $qrPaymentRequest->getPublicId(),
                Entity::TRANSACTION_REFERENCE => $qrPaymentRequest->getTransactionReference(),
            ]
        );

        return $qrPaymentRequest;
    }

    public function update($qrPaymentRequest)
    {
        $this->trace->info(
            TraceCode::QR_PAYMENT_UPDATE_REQUEST,
            [
                Entity::ID                    => $qrPaymentRequest->getPublicId(),
                Entity::QR_CODE_ID            => $qrPaymentRequest->getQrCodeId(),
                Entity::TRANSACTION_REFERENCE => $qrPaymentRequest->getTransactionReference(),
            ]
        );

        $this->repo->save($qrPaymentRequest);
    }
}
