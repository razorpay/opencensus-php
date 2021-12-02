<?php

namespace RZP\Models\VirtualAccount;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Settings;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Base\ConnectionType;
use RZP\Models\Settings\Module;
use RZP\Models\VirtualAccountTpv;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants;
use RZP\Models\VirtualAccountProducts;
use RZP\Models\Offline\Device as OfflineDevice;

class Service extends Base\Service
{
    protected $core;

    const DEFAULT_RECEIVER_TYPES = [
        Receiver::BANK_ACCOUNT,
    ];

    const VA_ADD_RECEIVER            = 'va_add_receiver';
    const VA_ADD_ALLOWED_PAYER       = 'va_add_allowed_payer';
    const VA_DELETE_ALLOWED_PAYER    = 'va_delete_allowed_payer';

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_REQUEST,
                           $this->removePiiForLogging($input));

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

        $this->pushVaEventToDataLake($virtualAccount, EventCode::VIRTUAL_ACCOUNT_CREATED);

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

                if ($virtualAccount === null)
                {
                    $virtualAccount = $this->getVirtualAccountForCustomer($input, $order);
                }

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

                $this->addCloseBy($createArray, $input);

                if (isset($input[Entity::CUSTOMER_ID]) === true)
                {
                    $createArray[Entity::CUSTOMER_ID] = $input[Entity::CUSTOMER_ID];
                }

                $virtualAccount = $this->create($createArray);

                $this->editAmountExpectedToIncludeFees($order, $virtualAccount);

                return $virtualAccount;
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);

        return $response;
    }

    private function addCloseBy(&$createArray, $input)
    {
        if ($this->merchant->getOrgId() === Admin\Org\Entity::HDFC_ORG_ID)
        {
            $expirySetting = $this->getMerchantDefaultVirtualAccountExpiry();

            $expirySetting = $expirySetting === -1 ? Constant::ECMS_CHALLAN_DEFAULT_EXPIRY : $expirySetting;

            $createArray[Entity::CLOSE_BY] = Carbon::today(Timezone::IST)
                                                   ->addDays($expirySetting)
                                                   ->getTimestamp();
        }
        else if (isset($input[Entity::CLOSE_BY]) === true)
        {
            $createArray[Entity::CLOSE_BY] =  $input[Entity::CLOSE_BY];
        }
    }

    /*
     * This can be considered as a generic function for all apps to call.
     * all the validations not needed in the normal create can be added here
     */
    public function createForInternal(array $input)
    {
        if (isset($input[Entity::ORDER_ID]) === true)
        {
            $orderId = $input[Entity::ORDER_ID];

            $order = $this->repo
                ->order
                ->findByPublicIdAndMerchant($orderId, $this->merchant);

            if ($order === null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER);
            }

            if ($order->isPaid() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER);
            }

            $virtualAccount = $this->repo
                ->virtual_account
                ->findActiveVirtualAccountByOrder($order);

            if ($virtualAccount !== null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER);
            }
        }

        return $this->create($input);
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
        if (isset($input[Entity::BALANCE_ID]) === false)
        {
            $input[Entity::BALANCE_ID] = $this->merchant->primaryBalance->getId();
        }

        $virtualAccounts = $this->repo
                                ->virtual_account
                                ->fetch($input, $this->merchant->getId());

        return $virtualAccounts->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_EDIT_REQUEST,
                           [
                               'id'   => $id,
                               'data' => $input
                           ]);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $virtualAccount->getValidator()->validateOfPrimaryBalance();

        $virtualAccount = $this->core->edit($virtualAccount, $input);

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_EDITED,
                           $virtualAccount->toArrayPublic()
        );

        return $virtualAccount->toArrayPublic();
    }

    public function closeVirtualAccountsByCloseBy()
    {
        $virtualAccountIds = $this->repo
                                ->virtual_account
                                ->fetchVirtualAccountsToBeClosed();

        $success = $failure = 0;

        $failures = [];

        foreach ($virtualAccountIds as $virtualAccountId)
        {
            try
            {
                $virtualAccount = $this->repo->virtual_account->find($virtualAccountId);

                $this->core->close($virtualAccount);

                $success++;

                $this->pushVaEventToDataLake($virtualAccount, EventCode::VIRTUAL_ACCOUNT_CLOSED, 'cron');
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failures[] = $virtualAccountId;
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

    public function bulkCloseVirtualAccount($input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_MERCHANTS_BULK_CLOSE_REQUEST, $input);

        if (isset($input['merchant_ids']) and isset($input['virtual_account_ids']))
        {
            return ['message' => 'Please pass either merchant ids or virtual account ids'];
        }

        (new Validator())->validateInput('bulkCloseVirtualAccount', $input);

        $closedVirtualAccountIds = [];
        $failedVirtualAccountIds = [];

        if (isset($input['merchant_ids']) === true)
        {
            foreach ($input['merchant_ids'] as $merchantId)
            {
                do
                {
                    $virtualAccounts = $this->repo
                                            ->virtual_account
                                            ->fetchActiveVirtualAccountsForMerchantId($merchantId,
                                                                                      array_keys($failedVirtualAccountIds),
                                                                                      Constant::FETCH_LIMIT);

                    $this->closeMultipleVirtualAccounts($virtualAccounts,
                                                        $closedVirtualAccountIds,
                                                        $failedVirtualAccountIds);
                } while (sizeof($virtualAccounts) === Constant::FETCH_LIMIT);
            }
        }
        else if (isset($input['virtual_account_ids']) === true)
        {
            $virtualAccounts = $this->repo
                                    ->virtual_account
                                    ->fetchActiveVirtualAccountIds($input['virtual_account_ids']);

            $this->closeMultipleVirtualAccounts($virtualAccounts,
                                                $closedVirtualAccountIds,
                                                $failedVirtualAccountIds);
        }

        $response = [
            'success' => $closedVirtualAccountIds,
            'failed'  => $failedVirtualAccountIds
        ];

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_MERCHANTS_BULK_CLOSE_RESPONSE, $response);

        return $response;
    }

    private function closeMultipleVirtualAccounts(
        array $virtualAccounts,
        &$closedVirtualAccountIds,
        &$failedVirtualAccountIds
    )
    {
        foreach ($virtualAccounts as $virtualAccount)
        {
            try
            {
                array_push($closedVirtualAccountIds, $this->closeVirtualAccountEntity($virtualAccount)['id']);
            }
            catch (\Throwable $ex)
            {
                $failedVirtualAccountIds = array_merge($failedVirtualAccountIds, [
                    $virtualAccount->getId()   =>  $ex->getMessage()
                ]);

                $this->trace->traceException(
                    $ex, Trace::ERROR, TraceCode::VIRTUAL_ACCOUNT_MERCHANTS_BULK_CLOSE_FAILURES,
                    [
                        'virtual_account_id' => $virtualAccount->getId(),
                        'mechant_id'         => $virtualAccount->merchant->getId(),
                    ]);
            }
        }
    }

    public function closeVirtualAccount(string $id)
    {
        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->closeVirtualAccountEntity($virtualAccount);
    }

    private function closeVirtualAccountEntity($virtualAccount)
    {
        $virtualAccount = $this->core->close($virtualAccount);

        $this->pushVaEventToDataLake($virtualAccount, EventCode::VIRTUAL_ACCOUNT_CLOSED);

        return $virtualAccount->toArrayPublic();
    }

    public function fetchPayments(string $virtualAccountId, array $input)
    {
        $input[Payment\Entity::VIRTUAL_ACCOUNT_ID] = $virtualAccountId;

        $merchantId = $this->merchant->getId();

        $payments = $this->repo->payment->fetch($input, $merchantId, ConnectionType::SLAVE);

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

    public function addReceivers(string $id, array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ADD_RECEIVER, $input);

        (new Validator())->validateInput('addReceivers', $input);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        if ($virtualAccount->hasOrder() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_RECEIVER_WITH_ORDER);
        }

        if ($virtualAccount->isClosed())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE);
        }

        $this->verifyMerchantCategory();

        $this->verifyMerchantIsLiveForLiveRequest();

        $virtualAccount = $this->mutex->acquireAndRelease(
            self::VA_ADD_RECEIVER . "_" . $virtualAccount->getPublicId(),
            function() use ($input, $virtualAccount)
            {
                return $this->core->addReceivers($virtualAccount, $input);
            },
            10,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_RECEIVER_IN_PROGRESS,
            5,
            200,
            400
        );

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

    public function bulkMigrateYesbank(array $input)
    {
        return (new Core)->bulkMigrateYesbank($input);
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

    public function createForBanking(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_FOR_BANKING_REQUEST, $input);

        (new Validator)->validateInput('create_for_banking', $input);

        /** @var \RZP\Models\Merchant\Validator $validator */
        $validator = $this->merchant->getValidator();

        $validator->validateBusinessBankingActivated();

        if ($this->mode === Mode::LIVE)
        {
            $validator->validateIsActivated($this->merchant);
        }

        $virtualAccount = $this->core->createForBankingBalance($this->merchant,
            $this->merchant->sharedBankingBalance,
            $input);

        return $virtualAccount->toArrayPublic();
    }

    public function bulkCreateForBanking(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_BULK_CREATE_FOR_BANKING_REQUEST, $input);

        $merchantIds = $input['merchant_ids'] ?? [];

        $success = $failure = 0;

        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                /** @var Merchant\Entity $merchant */
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if ($merchant->isBusinessBankingEnabled() === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                        null,
                        [
                            'merchant_id' => $merchantId
                        ]
                    );
                }

                $this->app['basicauth']->setMerchant($merchant);

                $this->core->createForBankingBalance($merchant, $merchant->sharedBankingBalance);

                $success++;
            }
            catch (\Throwable $e)
            {
                $failure++;

                $failures[] = $merchantId;

                $this->trace->traceException($e,
                    Trace::ERROR,
                    TraceCode::VIRTUAL_ACCOUNT_CREATE_FOR_BANKING_FAILED,
                    [
                        'merchant_id' => $merchantId
                    ]);
            }
        }

        $response = [
            'success'  => $success,
            'failure'  => $failure,
            'failures' => $failures,
        ];

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_BULK_CREATE_FOR_BANKING_RESPONSE, $response);

        return $response;
    }

    public function bulkCloseForBanking(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_BULK_CLOSE_FOR_BANKING_REQUEST, $input);

        $virtualAccountIds = $input['virtual_account_ids'] ?? [];

        $success = $failure = 0;

        $failures = [];

        foreach ($virtualAccountIds as $virtualAccountId)
        {
            try
            {
                $virtualAccount = $this->repo
                                       ->virtual_account
                                       ->findByPublicIdWithRelations($virtualAccountId, ['bankAccount']);

                $this->core->closeForBanking($virtualAccount);

                $success++;
            }
            catch (\Throwable $e)
            {
                $failure++;

                $failures[] = $virtualAccountId;

                $this->trace->traceException($e,
                    Trace::ERROR,
                    TraceCode::VIRTUAL_ACCOUNT_CLOSE_FOR_BANKING_FAILED,
                    [
                        'id' => $virtualAccountId
                    ]);
            }
        }

        $response = [
            'success'  => $success,
            'failure'  => $failure,
            'failures' => $failures,
        ];

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_BULK_CLOSE_FOR_BANKING_RESPONSE, $response);

        return $response;
    }

    protected function pushVaEventToDataLake(Entity $virtualAccount, array $eventCode, string $source = '')
    {
        $properties = ['source' => 'api'];

        if ($source !== '')
        {
            $properties['source'] = $source;
        }
        else if ($this->auth->isDashboardApp() === true)
        {
            $properties['source'] = 'dashboard';
        }
        else if ($this->app['request.ctx']->getRoute() === 'virtual_account_order_create')
        {
            $properties['source'] = 'checkout';
        }
        else if ($this->auth->isPaymentLinkServiceApp() === true)
        {
            $properties['source'] = SourceType::PAYMENT_LINKS_V2;
        }

        $this->app['diag']->trackVirtualAccountEvent(
            $eventCode,
            $virtualAccount,
            null,
            $properties
        );
    }

    public function ecollectValidateVpa(string $vpa, $input)
    {
        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_ECOLLECT_VALIDATE_VPA_PROCESSING,
            [
                'vpaUsername' => $vpa,
            ]);

        $response['valid'] = false;

        $this->determineAndSetMode();

        $vpa = $this->repo->vpa->findByAddress($vpa, true);

        if ($vpa === null)
        {
            if (str_starts_with($input['SubscriberId'], 'qr'))
            {
                $response['valid'] = true;

                $response['merchantName'] = 'Razorpay QR Payment';
            }

            return $response;
        }

        $vpaSource = $vpa->virtualAccount;

        if ($vpaSource === null)
        {
            $vpaSource = $vpa->qrCode;
        }

        if (($vpaSource !== null) and ($vpaSource->getStatus() === Status::ACTIVE))
        {
            $response['valid'] = true;

            $response['merchantName'] = $vpa->merchant->getBillingLabel();
        }

        return $response;
    }

    protected function determineAndSetMode()
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Gets mode per route and sets application & db mode.
        $this->mode = str_contains($routeName, 'test') ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($this->mode);
    }

    protected function getVirtualAccountForCustomer(array & $input, Order\Entity $order)
    {
        $customer = $this->getCustomerIfGiven($input);

        if ($customer === null)
        {
            return null;
        }

        if (empty($input[Entity::CUSTOMER]) === false)
        {
            $input[Entity::CUSTOMER_ID] = $customer->getPublicId();
        }

        if ($this->merchant->isFeatureEnabled(Constants::CHECKOUT_VA_WITH_CUSTOMER) === false)
        {
            $this->trace->info(TraceCode::MERCHANT_FEATURE_NOT_EXIST);
            return null;
        }

        /*
         * Below flow is for single VA on checkout where for a customer,
         * single VA is to be created/updated w.r.t multiple orders
         *
         * In order to enable single VA on checkout, customer_id needs to be passed in the request
         */
        $virtualAccount = $this->repo
                                ->virtual_account
                                ->findActiveVirtualAccountForOrderByCustomer($customer);
        if ($virtualAccount === null)
        {
            return null;
        }

        $editArray = [
            Entity::AMOUNT_EXPECTED => $order->getAmountDue(),
            Entity::AMOUNT_PAID     => 0,
            Entity::AMOUNT_RECEIVED => 0,
            Entity::NOTES           => $input[Entity::NOTES] ?? [],
            Entity::STATUS          => Status::ACTIVE,
        ];

        if (isset($input[Entity::CLOSE_BY]) === true)
        {
            $editArray[Entity::CLOSE_BY] =  $input[Entity::CLOSE_BY];
        }

        $this->repo->transaction(function() use ($virtualAccount, $order, $editArray)
        {
            $virtualAccount->entity()->associate($order);
            $virtualAccount->edit($editArray, 'editForOrder');

            (new VirtualAccountProducts\Core())->create($virtualAccount);

            $this->repo->saveOrFail($virtualAccount);
        });

        return $virtualAccount;
    }


    public function addDefaultVirtualAccountExpiry($input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_MERCHANT_EXPIRY_SETTING_UPSERT_REQUEST, $input);

        (new Validator())->validateInput('defaultVAExpiry', $input);

        try
        {
            (new Settings\Service())->upsert(Module::VIRTUAL_ACCOUNT, $input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::VIRTUAL_ACCOUNT_MERCHANT_EXPIRY_SETTING_UPSERT_FAILED,
                [
                    'merchant_id' => $this->merchant->getPublicId(),
                    'input'       => $input,
                ]
            );

            return ['success' => false];
        }

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_MERCHANT_EXPIRY_SETTING_UPSERT_SUCCESS,
                           [
                               'merchant_id' => $this->merchant->getPublicId(),
                               'success'     => true,
                               'input'       => $input
                           ]);

        return ['success' => true];
    }

    public function getMerchantDefaultVirtualAccountExpiry()
    {
        try
        {
            $response = (new Settings\Service())->get(Module::VIRTUAL_ACCOUNT,
                                                      Constant::ECMS_VA_EXPIRY_OFFSET_SETTING_KEY);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::VIRTUAL_ACCOUNT_MERCHANT_EXPIRY_SETTING_FETCH_FAILED,
                [
                    'merchant_id' => $this->merchant->getPublicId(),
                ]
            );

            return -1;
        }

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_MERCHANT_EXPIRY_SETTING_GET_RESPONSE,
                           [
                               'merchant_id' => $this->merchant->getPublicId(),
                               'response'    => $response
                           ]);

        return is_string($response['settings']) ? (int) $response['settings'] : -1;
    }

    public function addAllowedPayer($id, $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ALLOWED_PAYER_ADD_REQUEST, $input);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        if ($virtualAccount->isClosed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED);
        }

        if ($virtualAccount->virtualAccountTpv()->count() >= 10)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_LIMIT_EXCEEDED);
        }

        $virtualAccount = $this->mutex->acquireAndRelease(
            self::VA_ADD_ALLOWED_PAYER . "_" . $virtualAccount->getPublicId(),
            function() use ($input, $virtualAccount)
            {
                return (new VirtualAccountTpv\Core())->addAllowedPayerToExistingVa($virtualAccount, $input);
            },
            10,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_IN_PROGRESS,
            5,
            200,
            400
        );

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ALLOWED_PAYER_ADDED, $virtualAccount->toArrayPublic());

        return $virtualAccount->toArrayPublic();
    }

    public function deleteAllowedPayer($virtualAccountId, $tpvId)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ALLOWED_PAYER_DELETE_REQUEST);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($virtualAccountId, $this->merchant);

        if ($virtualAccount->isClosed() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED,
            'virtual_account_id');
        }

        $this->mutex->acquireAndRelease(
            self::VA_DELETE_ALLOWED_PAYER . "_" . $virtualAccount->getPublicId(),
            function() use ($tpvId, $virtualAccount)
            {
                return (new VirtualAccountTpv\Core())->deleteAllowedPayer($virtualAccount, $tpvId);
            },
            10,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_RECEIVER_IN_PROGRESS,
            5,
            200,
            400
        );
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_ALLOWED_PAYER_DELETED);

    }

    public function removePiiForLogging(array $input)
    {
        if(isset($input['allowed_payers']) == false)
        {
            return $input;
        }

        foreach($input['allowed_payers'] as &$allowed_payer)
        {
            if ((isset($allowed_payer['bank_account']) === false) or
                (isset($allowed_payer['bank_account']['account_number']) === false))
            {
                continue;
            }

            $account_number = $allowed_payer['bank_account']['account_number'];

            $repeat = ceil((strlen($account_number) - 4) / 4);

            $allowed_payer['bank_account']['account_number'] = str_repeat('XXXX-', $repeat) . substr($account_number, -4);
        }

        return $input;
    }
}
