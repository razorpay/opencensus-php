<?php

namespace RZP\Models\QrPaymentRequest;

use App;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';

    const QR_CODE_ID            = 'qr_code_id';

    /*
     *  Contains unique payment reference
     * - provider_reference_id in case of bharat_qr
     * - npci_txn_id in case of upi_qr type
     */
    const TRANSACTION_REFERENCE = 'transaction_reference';

    const EXPECTED              = 'expected';

    const REQUEST_SOURCE        = 'request_source';

    // This refers to bharat_qr table's id
    const BHARAT_QR_ID          = 'bharat_qr_id';

    // This refer's to upi table's id
    const UPI_ID                = 'upi_id';

    // All the payment failures including unexpected
    // reason will be stored in this field
    const FAILURE_REASON        = 'failure_reason';

    const IS_CREATED            = 'is_created';

    const REQUEST_PAYLOAD       = 'request_payload';

    protected static $sign = 'qpr';

    protected $entity = 'qr_payment_request';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::QR_CODE_ID,
        self::TRANSACTION_REFERENCE,
        self::EXPECTED,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::BHARAT_QR_ID,
        self::UPI_ID,
        self::REQUEST_SOURCE,
        self::REQUEST_PAYLOAD,
        self::IS_CREATED,
    ];

    protected $visible = [
        self::ID,
        self::QR_CODE_ID,
        self::TRANSACTION_REFERENCE,
        self::EXPECTED,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::UPI_ID,
        self::BHARAT_QR_ID,
        self::REQUEST_SOURCE,
        self::REQUEST_PAYLOAD,
        self::IS_CREATED,
    ];

    public function getTransactionReference()
    {
        return $this->getAttribute(self::TRANSACTION_REFERENCE);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getQrCodeId()
    {
        return $this->getAttribute(self::QR_CODE_ID);
    }

    public function isCreated()
    {
        return $this->getAttribute(self::IS_CREATED);
    }

    public function setRequestSource($requestSource)
    {
        $this->setAttribute(self::REQUEST_SOURCE, $requestSource);
    }

    public function setRequestPayload($requestPayload)
    {
        $this->setAttribute(self::REQUEST_PAYLOAD, json_encode($requestPayload));
    }

    public function setFailureReason($failureReason)
    {
        $this->setAttribute(self::FAILURE_REASON, substr($failureReason, 0, 254));
    }

    public function setFailureReasonIfNotSet($failureReason)
    {
        if ($this->getAttribute(self::FAILURE_REASON) === null)
        {
            $this->setFailureReason($failureReason);
        }
    }

    public function setCreatedIfNotSet($qrPaymentEntity)
    {
        if ($this->getAttribute(self::IS_CREATED) === null)
        {
            $this->setCreated($qrPaymentEntity);
        }
    }

    public function setCreated(bool $isCreated)
    {
        $this->setAttribute(self::IS_CREATED, $isCreated);
    }

    public function setExpectedIfNotSet($isExpected)
    {
        if ($this->getAttribute(self::EXPECTED) === null)
        {
            $this->setExpected($isExpected);
        }
    }

    public function setExpected($isExpected)
    {
        $this->setAttribute(self::EXPECTED, $isExpected);
    }

    public function setQrPaymentEntity($entity, $type)
    {
        if ($entity === null)
        {
            return;
        }

        switch ($type)
        {
            case Type::BHARAT_QR:
                $this->setBharatQrId($entity->getId());
                break;

            case Type::UPI_QR:
                $this->setUpiId($entity->getId());
                break;
        }
    }

    public function setBharatQrId($id)
    {
        $this->setAttribute(self::BHARAT_QR_ID, $id);
    }

    public function setUpiId($id)
    {
        $this->setAttribute(self::UPI_ID, $id);
    }

    public function findAndSetRequestSource()
    {
        $app = App::getFacadeRoot();

        $routeName = $app['api.route']->getCurrentRouteName();

        $requestSource = [];

        switch ($routeName)
        {
            case 'gateway_payment_callback_get':
            case 'gateway_payment_callback_post':
            case 'gateway_payment_callback_bharatqr':
                $requestSource = [
                    'source'        => 'callback',
                    'request_from'  => 'bank',
                ];

                break;

            case 'reconciliate_via_batch_service':
            case 'payment_callback_bharatqr_internal':
                $requestSource = [
                    'source'        => 'file',
                    'request_from'  => 'bank',
                ];

                break;

            default:
                $app['trace']->info(
                    TraceCode::UNTRACKED_ENDPOINT_QR_CODE_PAYMENT,
                    [
                        'route_name'    => $routeName,
                        'utr'           => $this->getTransactionReference(),
                    ]
                );

                break;
        }

        $this->setRequestSource(json_encode($requestSource));
    }

    public function getRequestPayload()
    {
        return $this->getAttribute(self::REQUEST_PAYLOAD);
    }
}
