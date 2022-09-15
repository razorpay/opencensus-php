<?php


namespace RZP\Models\BankTransferRequest;

use App;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const GATEWAY           = 'gateway';
    const IS_CREATED        = 'is_created';
    const ERROR_MESSAGE     = 'error_message';
    const UTR               = 'utr';
    const MODE              = 'mode';
    const PAYEE_NAME        = 'payee_name';
    const PAYEE_ACCOUNT     = 'payee_account';
    const PAYEE_IFSC        = 'payee_ifsc';
    const PAYER_NAME        = 'payer_name';
    const PAYER_ACCOUNT     = 'payer_account';
    const PAYER_IFSC        = 'payer_ifsc';
    const AMOUNT            = 'amount';
    const DESCRIPTION       = 'description';
    const NARRATION         = 'narration';
    const TIME              = 'time';
    const REQUEST_PAYLOAD   = 'request_payload';
    const REQUEST_SOURCE    = 'request_source';

    const TRANSACTION_ID        = 'transaction_id';
    const PAYER_ACCOUNT_TYPE    = 'payer_account_type';
    const PAYER_ADDRESS         = 'payer_address';
    const CURRENCY              = 'currency';
    const ATTEMPT               = 'attempt';

    const INTENDED_VIRTUAL_ACCOUNT_ID   = 'intended_virtual_account_id';
    const ACTUAL_VIRTUAL_ACCOUNT_ID     = 'actual_virtual_account_id';
    const BANK_TRANSFER_ID              = 'bank_transfer_id';
    const PAYMENT_ID                    = 'payment_id';
    const ORDER_ID                      = 'order_id';
    const PRODUCT_TYPE                  = 'product_type';
    const PRODUCT_ID                    = 'product_id';
    const MERCHANT_NAME                 = 'merchant_name';

    const BANK_TRANSFER_REQUEST_ID      = 'bank_transfer_request_id';

    // Input keys
    const FIRST_TIME_ON_TEST_MODE = 'first_time_on_test_mode';

    protected static $sign = 'btr';

    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::GATEWAY,
        self::UTR,
        self::MODE,
        self::PAYEE_NAME,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NARRATION,
        self::TIME,
        self::REQUEST_PAYLOAD,
        self::REQUEST_SOURCE,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::BANK_TRANSFER_ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::IS_CREATED,
        self::ERROR_MESSAGE,
        self::UTR,
        self::TRANSACTION_ID,
        self::MODE,
        self::PAYEE_NAME,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NARRATION,
        self::TIME,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::BANK_TRANSFER_ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REQUEST_PAYLOAD,
        self::REQUEST_SOURCE,
    ];

    protected $bankTransferProcess = [
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYEE_NAME,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::MODE,
        self::TRANSACTION_ID,
        self::TIME,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NARRATION,
    ];

    protected $appends = [
        self::TRANSACTION_ID,
    ];

    protected $pii = [
        self::PAYEE_ACCOUNT,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::REQUEST_PAYLOAD,
    ];

    protected static $generators = [
        self::UTR,
    ];

    protected static $unsetCreateInput = [
        self::PAYER_ACCOUNT_TYPE,
        self::PAYER_ADDRESS,
        self::CURRENCY,
        self::ATTEMPT,
    ];

    protected $casts = [
        self::IS_CREATED    => 'bool',
        self::AMOUNT        => 'int',
    ];

    public function generateUtr($input)
    {
        $this->setAttribute(self::UTR, $input[self::TRANSACTION_ID]);
    }

    // -------------------- Getters --------------------

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getRequestSource()
    {
        return $this->getAttribute(self::REQUEST_SOURCE);
    }

    public function getPayeeAccount()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    // -------------------- End Getters --------------------

    // -------------------- Setters --------------------

    public function setAmountAttribute(float $amount)
    {
        $amount = (int) number_format(($amount * 100), 0, '.', '');

        $this->attributes[self::AMOUNT] = $amount;
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setUtr(string $utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setPayeeAccount($payeeAccount)
    {
        $this->setAttribute(self::PAYEE_ACCOUNT, $payeeAccount);
    }

    public function setRequestPayload($requestPayload)
    {
        $this->setAttribute(self::REQUEST_PAYLOAD, $requestPayload);
    }

    public function setErrorMessage($errorMessage)
    {
        $this->setAttribute(self::ERROR_MESSAGE, $errorMessage);
    }

    public function setIsCreated($isCreated)
    {
        $this->setAttribute(self::IS_CREATED, $isCreated);
    }

    public function setRequestSource($requestSource)
    {
        $this->setAttribute(self::REQUEST_SOURCE, $requestSource);
    }

    // -------------------- End Setters --------------------

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getTransactionIdAttribute()
    {
        return $this->getAttribute(self::UTR);
    }

    public function findAndSetRequestSource($routeName = null)
    {
        $app = App::getFacadeRoot();

        if ($routeName === null)
        {
            $routeName = $app['api.route']->getCurrentRouteName();
        }

        $requestSource = [];

        switch ($routeName)
        {
            case 'bank_transfer_process':
            case 'bank_transfer_process_rbl':
            case 'bank_transfer_process_icici':
            case 'bank_transfer_process_hdfc_ecms':
                $requestSource = [
                    'source'        => 'callback',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_process_rbl_internal':
            case 'bank_transfer_process_icici_internal':
            case 'bank_transfer_process_yesbank_internal':
                $requestSource = [
                    'source'        => 'file',
                    'request_from'  => 'bank',
                ];

                break;

            case 'bank_transfer_insert':
                $requestSource = [
                    'source'        => 'admin_dashboard',
                    'request_from'  => 'admin',
                ];

                break;

            case 'batch_create_admin':
                $requestSource = [
                    'source'        => 'file',
                    'request_from'  => 'admin',
                ];

                break;

            case 'bank_transfer_process_test_x_demo_cron':
            case 'bank_transfer_process_test':
            case 'bank_transfer_process_rbl_test':
                $requestSource = [
                    'source'       => 'test',
                    'request_from' => 'test',
                ];

                break;

            default:
                $app['trace']->info(
                    TraceCode::UNTRACKED_ENDPOINT_BANK_TRANSFER,
                    [
                        'route_name'    => $routeName,
                        'utr'           => $this->getUtr(),
                    ]
                );

                break;
        }

        if ($app['api.route']->getCurrentRouteName() === 'bank_transfer_process_internal')
        {
            $requestSource['sc_service_callback'] = true;
        }

        $this->setRequestSource(json_encode($requestSource));
    }

    public function getBankTransferProcessInput()
    {
        $array = $this->attributesToArray();

        $array[self::AMOUNT] = $this->getAmount() / 100;

        return array_only($array, $this->bankTransferProcess);
    }

    public function toArrayTrace(): array
    {
        $data = $this->toArray();

        foreach ($this->pii as $piiField)
        {
            if (isset($data[$piiField]) === false)
            {
                continue;
            }

            switch ($piiField)
            {
                case self::PAYEE_ACCOUNT:
                    $payeeAccount = $data[Entity::PAYEE_ACCOUNT];

                    $data[Entity::PAYEE_ACCOUNT . '_prefix']        = substr($payeeAccount, 0, 8);
                    $data[Entity::PAYEE_ACCOUNT . '_descriptor']    = substr($payeeAccount, 8, strlen($payeeAccount));

                    break;

                default:
                    break;
            }
            unset($data[$piiField]);
        }

        return $data;
    }
}
