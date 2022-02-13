<?php


namespace RZP\Models\UpiTransferRequest;

use App;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const GATEWAY                   = 'gateway';
    const IS_CREATED                = 'is_created';
    const ERROR_MESSAGE             = 'error_message';
    const NPCI_REFERENCE_ID         = 'npci_reference_id';
    const PAYEE_VPA                 = 'payee_vpa';
    const PAYER_VPA                 = 'payer_vpa';
    const PAYER_BANK                = 'payer_bank';
    const PAYER_ACCOUNT             = 'payer_account';
    const PAYER_IFSC                = 'payer_ifsc';
    const AMOUNT                    = 'amount';
    const GATEWAY_MERCHANT_ID       = 'gateway_merchant_id';
    const PROVIDER_REFERENCE_ID     = 'provider_reference_id';
    const TRANSACTION_REFERENCE     = 'transaction_reference';
    const TRANSACTION_TIME          = 'transaction_time';
    const REQUEST_PAYLOAD           = 'request_payload';
    const REQUEST_SOURCE            = 'request_source';

    const INTENDED_VIRTUAL_ACCOUNT_ID   = 'intended_virtual_account_id';
    const ACTUAL_VIRTUAL_ACCOUNT_ID     = 'actual_virtual_account_id';
    const UPI_TRANSFER_ID               = 'upi_transfer_id';
    const PAYMENT_ID                    = 'payment_id';
    const MERCHANT_NAME                 = 'merchant_name';

    protected static $sign = 'utr';

    protected $entity = Constants\Entity::UPI_TRANSFER_REQUEST;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::GATEWAY,
        self::NPCI_REFERENCE_ID,
        self::PAYEE_VPA,
        self::PAYER_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
        self::TRANSACTION_TIME,
        self::REQUEST_PAYLOAD,
        self::REQUEST_SOURCE,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::UPI_TRANSFER_ID,
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::IS_CREATED,
        self::ERROR_MESSAGE,
        self::NPCI_REFERENCE_ID,
        self::PAYEE_VPA,
        self::PAYER_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
        self::TRANSACTION_TIME,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::UPI_TRANSFER_ID,
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::IS_CREATED    => 'bool',
        self::AMOUNT        => 'int',
    ];

    // -------------------- Getters --------------------

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getNpciReferenceId()
    {
        return $this->getAttribute(self::NPCI_REFERENCE_ID);
    }

    public function getPayeeVpa()
    {
        return $this->getAttribute(self::PAYEE_VPA);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    // -------------------- End Getters --------------------

    // -------------------- Setters --------------------

    public function setGateway(string $gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setNpciReferenceId(string $npciReferenceId)
    {
        $this->setAttribute(self::NPCI_REFERENCE_ID, $npciReferenceId);
    }

    public function setRequestPayload($requestPayload)
    {
        $this->setAttribute(self::REQUEST_PAYLOAD, $requestPayload);
    }

    public function setRequestSource($requestSource)
    {
        $this->setAttribute(self::REQUEST_SOURCE, $requestSource);
    }

    public function setErrorMessage($errorMessage)
    {
        $this->setAttribute(self::ERROR_MESSAGE, $errorMessage);
    }

    public function setIsCreated($isCreated)
    {
        $this->setAttribute(self::IS_CREATED, $isCreated);
    }

    // -------------------- End Setters --------------------
    public function findAndSetRequestSource()
    {
        $app = App::getFacadeRoot();

        $routeName = $app['api.route']->getCurrentRouteName();

        $requestSource = [];

        switch ($routeName)
        {
            case 'upi_transfer_process':
                $requestSource = [
                    'source'       => 'callback',
                    'request_from' => 'bank',
                ];

                break;

            case 'reconciliate':
            case 'reconciliate_via_batch_service':
            case 'upi_transfer_process_internal':
                $requestSource = [
                    'source'       => 'recon',
                    'request_from' => 'admin',
                ];

                break;

            case 'upi_transfer_process_test':
                $requestSource = [
                    'source'       => 'test',
                    'request_from' => 'test',
                ];

                break;

            default:
                $app['trace']->info(
                    TraceCode::UNTRACKED_ENDPOINT_UPI_TRANSFER,
                    [
                        'route_name'  => $routeName,
                        'npci_ref_id' => $this->getNpciReferenceId(),
                    ]);

                break;
        }
        $this->setRequestSource(json_encode($requestSource));
    }
}
