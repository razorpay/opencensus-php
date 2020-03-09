<?php

namespace RZP\Models\VirtualAccount;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\BankTransfer;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\QrCode;
use RZP\Models\Currency\Currency;
use RZP\Models\Offline\Device as OfflineDevice;

class Service extends Base\Service
{
    protected $core;

    const DEFAULT_RECEIVER_TYPES = [
        Receiver::BANK_ACCOUNT,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_REQUEST, $input);

        $this->verifyMerchantCategory();

        $this->verifyMerchantIsLiveForLiveRequest();

        $customer = $this->getCustomerIfGiven($input);

        $order = $this->getOrderIfGiven($input);

        $this->modifyRequestFromOldFormat($input);

        (new Validator)->validateDefaultCloseBy($input);

        $virtualAccount = $this->core->create($input, $this->merchant, $customer, $order);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_CREATED,
            $virtualAccount->toArrayPublic()
        );

        return $virtualAccount->toArrayPublic();
    }

    public function createForOrder(string $orderId, array $input)
    {
        $order = $this->repo
                      ->order
                      ->findByPublicIdAndMerchant($orderId, $this->merchant);

        if ($order->isPaid() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER);
        }

        $response = $this->mutex->acquireAndRelease(
            $orderId,
            function() use ($order, $input)
            {
                $virtualAccount = $this->repo
                                       ->virtual_account
                                       ->findActiveVirtualAccountByOrder($order);

                if ($virtualAccount !== null)
                {
                    $virtualAccount = $virtualAccount->toArrayPublic();

                    $this->editAmountExpectedToIncludeFees($order, $virtualAccount);

                    return $virtualAccount;
                }

                $createArray = [
                    Entity::ORDER_ID        => $order->getPublicId(),
                    Entity::AMOUNT_EXPECTED => $order->getAmountDue(),
                    Entity::NOTES           => $input[Entity::NOTES] ?? [],
                    Entity::RECEIVERS       => [
                        Entity::TYPES => [
                            Receiver::BANK_ACCOUNT,
                        ],
                    ],
                ];

                if (isset($input[Entity::CLOSE_BY]) === true)
                {
                    $createArray[Entity::CLOSE_BY] =  $input[Entity::CLOSE_BY];
                }

                $virtualAccount = $this->create($createArray);

                $this->editAmountExpectedToIncludeFees($order, $virtualAccount);

                return $virtualAccount;
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

        return $response;
    }

    protected function editAmountExpectedToIncludeFees(Order\Entity $order, array & $virtualAccount)
    {
        if ($order->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            $amountExpected = $this->getExpectedAmountForVirtualAccount($order);

            $virtualAccount[Entity::AMOUNT_EXPECTED] = $amountExpected;
        }
    }

    protected function getExpectedAmountForVirtualAccount(Order\Entity $order)
    {
        $fee = (new BankTransfer\Core)->getFeesForOrder($order);

        return ($order->getAmountDue() + $fee);
    }

    public function fetch(string $id)
    {
        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchantWithRelations(
                                    $id,
                                    $this->merchant,
                                    ['bankAccount']);

        return $virtualAccount->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $input[Entity::BALANCE_ID] = $this->merchant->primaryBalance->getId();

        $virtualAccounts = $this->repo
                                ->virtual_account
                                ->fetch($input, $this->merchant->getId());

        return $virtualAccounts->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $virtualAccount->getValidator()->validateOfPrimaryBalance();

        $virtualAccount = $this->core->edit($virtualAccount, $input);

        return $virtualAccount->toArrayPublic();
    }

    public function closeVirtualAccountsByCloseBy()
    {
        $virtualAccounts = $this->repo
                                ->virtual_account
                                ->fetchVirtualAccountsToBeClosed();

        $success = $failure = 0;

        $failures = [];

        foreach ($virtualAccounts as $virtualAccount)
        {
            try
            {
                $this->core->close($virtualAccount);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failures[] = $virtualAccount->getPublicId();
            }
        }

        $response = [
            'success'  => $success,
            'failure'  => $failure,
            'failures' => $failures,
        ];

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_CLOSED_ACCOUNTS,
            $response
        );

        return $response;
    }

    public function closeVirtualAccount(string $id)
    {
        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $virtualAccount = $this->core->close($virtualAccount);

        return $virtualAccount->toArrayPublic();
    }

    public function fetchPayments(string $virtualAccountId, array $input)
    {
        $input[Payment\Entity::VIRTUAL_ACCOUNT_ID] = $virtualAccountId;

        $merchantId = $this->merchant->getId();

        $payments = $this->repo->payment->fetch($input, $merchantId, true);

        return $payments->toArrayPublic();
    }

    /*
     * If customer_id is there it will return customer based on that,
     * otherwise if any of customer name, email or contact is given
     * then it will create customer based on that and return that customer.
     */

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
            return $customer;
        }

        if (empty($input[Entity::CUSTOMER]) === false)
        {
            $customer = (new Customer\Core())->createLocalCustomer($input[Entity::CUSTOMER], $this->merchant, false);
        }

        return $customer;
    }

    protected function getOrderIfGiven(array $input)
    {
        $order = null;

        if (isset($input[Entity::ORDER_ID]) === true)
        {
            $orderId = $input[Entity::ORDER_ID];

            $order = $this->repo
                          ->order
                          ->findByPublicIdAndMerchant($orderId, $this->merchant);
        }

        return $order;
    }

    protected function verifyMerchantCategory()
    {
        if ($this->merchant->isCategory2Cryptocurrency() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ACCOUNT);
        }
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    /**
     * Old format:
     * {
     *   "receiver_types": [
     *     "bank_account"
     *   ],
     *   "descriptor": "DESCRIPT"
     * }
     *
     * New format:
     * {
     *   "receivers": {
     *     "types": [
     *       "bank_account"
     *     ],
     *     "bank_account": {
     *       "descriptor": "DESCRIPT"
     *     }
     *   }
     * }
     *
     * Both formats are to be concurrently supported. While the old
     * format gave alphanumeric accounts by default, the new format will
     * give numeric ones by default. Here, we convert the old format to
     * the new one, and set numeric option to false explicitly, so that
     * the old format continues to work the way it did.
     *
     * @param  array $input
     */
    protected function modifyRequestFromOldFormat(array & $input)
    {
        if ($this->isOldFormat($input) === false)
        {
            return;
        }

        $types = $input[Entity::RECEIVER_TYPES];

        unset($input[Entity::RECEIVER_TYPES]);

        // Sending types as a single value was also allowed in the older format
        if (is_array($types) === false)
        {
            $types = [$types];
        }

        $input[Entity::RECEIVERS] = [
            Entity::TYPES => $types,
        ];
    }

    protected function isOldFormat(array $input): bool
    {
        if (isset($input[Entity::RECEIVER_TYPES]) === true)
        {
            return true;
        }

        return false;
    }

    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    public function addReceiver(string $id, array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ADD_RECEIVER, $input);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        if ($virtualAccount->isClosed())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE);
        }

        $this->verifyMerchantCategory();

        $this->verifyMerchantIsLiveForLiveRequest();

        $virtualAccount = $this->core->addReceiver($virtualAccount, $input, $this->merchant);

        return $virtualAccount->toArrayPublic();
    }

    public function createOfflineQr($input = [])
    {
        (new Entity)->validateInput('create_offline_qr', $input);

        $device = $this->getDeviceForQr($input);

        $order = $this->createOrder($input);

        // We don't any mutex here unlike create from order, since
        // we are creating the order in this request.

        $closeByTime = Carbon::now(Timezone::IST)->addSeconds(120)->getTimestamp();

        $createVaArray = [
            Entity::ORDER_ID        => $order->getPublicId(),
            Entity::AMOUNT_EXPECTED => $order->getAmount(),
            Entity::NOTES           => $input[Entity::NOTES] ?? [],
            Entity::RECEIVERS       => [
                Entity::TYPES => [
                    Receiver::QR_CODE,
                ],
            ],
            Entity::CLOSE_BY        => $closeByTime,
        ];

        $virtualAccount = $this->core->create($createVaArray, $this->merchant, null, $order);

        $this->pushToDeviceIfApplicable($device, $input, $virtualAccount);

        // Doing this separately since we don't want to affect the VA entity code
        $orderId = $order->getPublicId();

        $va = $virtualAccount->toArrayPublic();

        $va['order_id'] = $orderId;

        return $va;
    }

    protected function getDeviceForQr(array $input)
    {
        if (isset($input['notifications']['device_id']) === false)
        {
            return;
        }

        $device = $this->repo
                       ->offline_device
                       ->findByPublicIdAndMerchant($input['notifications']['device_id'], $this->merchant);

        if ($device === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Device Id provided is invalid.');
        }

        // We are locking a device, if the device is already lock that means it is in use.
        // We are relying on auto-release mechanism of the redis for this.
        $lockedAcquired = $this->mutex->acquire($device->getId(), 120);

        if ($lockedAcquired === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Device Id provided is already in use.');
        }

        return $device;
    }

    protected function pushToDeviceIfApplicable($device, $input, $virtualAccount)
    {
        if ($device === null)
        {
            return;
        }

        $currency = $input['currency'];

        $formattedAmount = Currency::getSymbol($currency) . ' ' . ($input['amount'] / Currency::getDenomination($currency));

        $payload = [
            'id'                => $virtualAccount->getPublicId(),
            'action'            => 'showqr',
            'qr_string'         => $virtualAccount->qrCode->getQrString(),
            'formatted_amount'  => $formattedAmount,
            'description'       => $virtualAccount->getDescription(),
            'close_by'          => $virtualAccount->getCloseBy(),
            'merchant_name'     => $this->merchant->getDbaName(),
        ];

        (new OfflineDevice\Service)->push($device, $payload);
    }

    protected function createOrder(array $input)
    {
        $orderInput = [
            Order\Entity::AMOUNT   => $input['amount'],
            Order\Entity::CURRENCY => $input['currency'],
            Order\Entity::RECEIPT  => $input['receipt'],
        ];

        return (new Order\Core)->create($orderInput, $this->merchant);
    }

    public function getConfigsForVirtualAccount()
    {
        $receivers[Entity::RECEIVER_TYPES] = [Receiver::BANK_ACCOUNT, Receiver::VPA];

        return $this->core->getConfigsForVirtualAccount($receivers);
    }
}
