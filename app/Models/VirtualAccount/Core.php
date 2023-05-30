<?php

namespace RZP\Models\VirtualAccount;

use Cache;
use Carbon\Carbon;
use RZP\Constants\Environment;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Base\BuilderEx;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Jobs\AppsRiskCheck;
use RZP\Models\EntityOrigin;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Balance;
use RZP\Models\VirtualAccountTpv;
use RZP\Models\Merchant\Constants;
use RZP\Jobs\VirtualAccountMigrate;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Order\Entity as Order;
use RZP\Models\VirtualAccountProducts;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Jobs\RblVirtualAccountCreateProcess;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as QrEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Status as QrStatus;
use RZP\Models\QrCode\NonVirtualAccountQrCode\UsageType as QrUsage;

class Core extends Base\Core
{
    const VA_BANK_ACCOUNT_GENERATION = 'va_bank_account_generation';

    const DEFAULT                    = 'default';

    const DORMANT_VA_START_DATE      = 'dormantva_start_date_';

    const DORMANT_VA_START_DATE_TTL  = 259200;      // 3days

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(
        array $input,
        Merchant $merchant,
        Customer\Entity $customer = null,
        Order $order = null,
        Balance\Entity $balance = null): Entity
    {
        //
        // VA creation is a bit broken at the moment. Creation requires multiple entities (VA+receivers)
        // to be committed to the DB, but while building receivers we also need to take a lock on the
        // generated account number and do a DB query to check for uniqueness. This will require a
        // refactor to be solved.
        //
        // For now, we're simply adding a global lock on VA creation to avoid duplicates being created.
        //
        try
        {
            $virtualAccount = $this->createEntityAndAssociate($merchant);

            if($this->app['basicauth']->isPaymentLinkServiceApp() === true)
            {
                $virtualAccount->setSource(SourceType::PAYMENT_LINKS_V2);
            }

            $virtualAccount = Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_ACQUIRE_AND_RELEASE], function() use($virtualAccount, $input, $merchant, $customer, $order, $balance)
            {
                return $this->mutex->acquireAndRelease(
                    self::VA_BANK_ACCOUNT_GENERATION . $virtualAccount->getId(),
                    function() use ($input, $merchant, $customer, $order, $balance, $virtualAccount)
                    {
                        return $this->buildVirtualAccountAndReceivers(
                            $virtualAccount, $input, $customer, $order, $balance);
                    },
                    // The entire VA creation process inside this lock actually takes
                    // an avg of 10ms, so 1000x i.e. 10 seconds is more than adequate TTL
                    10,
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
                    // A process will generally not need to do multiple retries at all,
                    // since the retry times are adequate for the previous process to complete.
                    5,
                    // 2x and 4x of avg response time for this entire route (not just the process within the lock)
                    200,
                    400);
            });


        }
        catch (\Throwable $e)
        {
            (new Metric)->pushCreateFailedMetrics($input, $e);

            throw $e;
        }

        $this->dispatchForRiskCheck($virtualAccount);

        (new Metric)->pushCreateSuccessMetrics($input);

        $createVirtualvariant  = $this->app->razorx->getTreatment($merchant->getId(),
                                                                  RazorxTreatment::BT_RBL_CREATE_VIRTUAL_ACCOUNT,
                                                                  $this->mode);
        if (($createVirtualvariant == 'on')  and
            ($virtualAccount->isBalanceTypeBanking() === false) and
            ($virtualAccount->bankAccount !== null) and
            in_array($virtualAccount->bankAccount->getIfscCode(), Provider::getGatewaySyncProvider()))
        {
            $bankAccount = $virtualAccount->bankAccount;
            $bankAccount->setIsGatewaySync(false);
            $this->repo->bank_account->saveOrFail($bankAccount);

            RblVirtualAccountCreateProcess::dispatch($this->mode, $virtualAccount->getId());
        }

        return $virtualAccount;
    }

    /**
     * A static qr code for all the unexpected payments is picked.
     */
    public function createOrFetchSharedVirtualAccount(array $options = [])
    {
        $virtualAccountId = Entity::SHARED_ID;

        $virtualAccount = $this->repo->virtual_account->find($virtualAccountId);

        if ($virtualAccount === null)
        {
            $virtualAccount = $this->createSharedVirtualAccount($options);
        }

        return $virtualAccount;
    }

    /**
     * We use this to get the common Virtual Account for all RazorpayX failed fund loading attempts.
     */
    public function fetchSharedBankingVirtualAccount()
    {
        $virtualAccountId = Entity::SHARED_ID_BANKING;

        return $this->repo->virtual_account->find($virtualAccountId);
    }

    /**
     * Creates a virtual account with bank account type receiver
     * on business banking type balance of given merchant.
     *
     * @param Merchant $merchant
     * @param Balance\Entity $balance
     * @param array $data
     * @return Entity
     */
    public function createForBankingBalance(Merchant $merchant, Balance\Entity $balance, array $data = []): Entity
    {
        $merchant->getValidator()->validateBusinessBankingActivated();

        $input = [
            Entity::RECEIVERS => [
                Entity::TYPES => [
                    Entity::BANK_ACCOUNT
                ],
            ],
        ];

        $input[Entity::NAME] = $this->getVaName($merchant, $data);

        return $this->create($input, $merchant, null, null, $balance);
    }

    /**
     * Since this route is exposed to the merchants now, they may want to pass
     * custom name for the VA. It's an optional param
     * If we receive name as input, we'll use that, otherwise fallback on the
     * default behaviour of VA, i.e.. using merchant.billing_label or merchant.name
     * In case everything is null, return default
     * @param  Merchant $merchant
     * @param  array $data
     * @return string
     */
    public function getVaName(Merchant $merchant, array $data) :string
    {
        $vaEntity = new \RZP\Models\VirtualAccount\Entity();

        $vaName = $vaEntity->getHighestPriorityName($merchant, $data) ?? Constants::DEFAULT;

        return $vaName;
    }

    public function createOrFetchBankingVirtualAccount(Merchant $merchant,
                                                       Balance\Entity $balance,
                                                       string $seriesPrefix): Entity
    {
        $virtualAccount = $this->fetchBankingVirtualAccounts($balance, $seriesPrefix);

        if ($virtualAccount === null)
        {
            $virtualAccount = $this->createForBankingBalance($merchant, $balance);
        }

        $accountNumber = trim(optional($virtualAccount->bankAccount)->getAccountNumber());

        // This happens when the terminal selection doesn't pick the
        // terminal we're expecting, i.e.. with the seriesPrefix
        // In this case, we should throw an exception
        if (starts_with($accountNumber, $seriesPrefix) === false)
        {
            throw new Exception\LogicException(
                'Virtual Account account number not be created for expected source account',
                null,
                [
                    'account_number'    => $accountNumber,
                    'source_prefix'     => $seriesPrefix,
                ]);
        }

        return $virtualAccount;
    }

    protected function fetchBankingVirtualAccounts(Balance\Entity $balance, string $seriesPrefix)
    {
        $virtualAccounts = $this->repo->virtual_account->getActiveVirtualAccountsFromBalanceId($balance->getId());

        // Since multiple VA can be linked to a balance
        // Therefore we want to figure out if a VA exists for the given seriesPrefix
        // If exists return that, else return null
        foreach ($virtualAccounts as $virtualAccount)
        {
            $accountNumber = trim(optional($virtualAccount->bankAccount)->getAccountNumber());

            if (starts_with($accountNumber, $seriesPrefix) === true)
            {
                return $virtualAccount;
            }
        }

        return null;
    }

    protected function buildVirtualAccountAndReceivers(
        Entity $virtualAccount,
        array $input,
        Customer\Entity $customer = null,
        Order $order = null,
        Balance\Entity $balance = null): Entity
    {
        $virtualAccount = $this->repo->transaction(function() use (
            $virtualAccount, $input, $customer, $order, $balance)
        {
            $virtualAccount->build($input);

            $virtualAccount->customer()->associate($customer);

            $virtualAccount->entity()->associate($order);

            $balance = $balance ?: $virtualAccount->merchant->primaryBalance;

            if (($balance !== null) and
                ($balance->getType() !== Balance\Type::PRIMARY) and
                ($input[Entity::RECEIVERS] === Receiver::OFFLINE_CHALLAN))
            {
                throw new Exception\LogicException(
                    'Invalid balance type, could not process payment.',
                    null,
                    [
                        'balanceType' => $balance->getType(),
                        'merchantId'  => $this->merchant->getId(),
                        'orderId'     => $order->getId()
                    ]);
            }

            $virtualAccount->balance()->associate($balance);

            $input = $this->updateQrCodeInput($virtualAccount, $input);

            $this->buildReceivers($virtualAccount, $input[Entity::RECEIVERS]);

            $virtualAccount = $this->updateVirtualAccountForHdfcUpiQR($virtualAccount,$input);

            Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_SAVE], function() use($virtualAccount)
            {
                $this->repo->virtual_account->saveOrFail($virtualAccount);
            });

            Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_VIRTUAL_ACCOUNT_PRODUCTS], function() use($virtualAccount)
            {
                (new VirtualAccountProducts\Core())->create($virtualAccount);
            });

            Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_ADD_ALLOWED_PAYER], function() use($virtualAccount, $input)
            {
                (new VirtualAccountTpv\Core())->buildAllowedPayers($virtualAccount, $input);
            });

            Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_CREATE_ORIGIN_ENTITY], function() use($virtualAccount)
            {
                (new EntityOrigin\Core)->createEntityOrigin($virtualAccount);
            });

            return $virtualAccount;
        });

        $this->repo->reload($virtualAccount);

        $this->eventVirtualAccountCreated($virtualAccount);

        return $virtualAccount;
    }

    protected function updateVirtualAccountForHdfcUpiQR($virtualAccount,$input)
    {
        if(isset($this->merchant) === true and ($this->merchant->isFeatureEnabled(Feature\Constants::UPIQR_V1_HDFC) === true)
            and isset($input[Entity::RECEIVERS][Entity::TYPES]) === true
            and $this->isUpiQr($input[Entity::RECEIVERS]) === true
            and isset($input[Entity::CLOSE_BY]) === true)
        {
            $virtualAccount->close_by = null;
        }

        return $virtualAccount;
    }

    protected function isUpiQr($input)
    {
        if($input[Entity::TYPES][0] === Entity::QR_CODE and isset($input[Entity::QR_CODE]['method']['card']) === true
            and isset($input[Entity::QR_CODE]['method']['upi']) === true
            and ($input[Entity::QR_CODE]['method']['card'] === false)
            and ($input[Entity::QR_CODE]['method']['upi'] === true))
        {
            return true;
        }
         return false;
    }

    protected function updateQrCodeInput($virtualAccount,$input): array
    {
        if(($virtualAccount->merchant->isFeatureEnabled(Feature\Constants::UPIQR_V1_HDFC) === true)
            and (isset($input[Entity::RECEIVERS][Entity::QR_CODE]) === true))
        {
            $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::STATUS] = QrStatus::ACTIVE;

            if(isset($input['usage']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::REQ_USAGE_TYPE] = $input[QrEntity::REQ_USAGE_TYPE];
            }
            else {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::REQ_USAGE_TYPE] = QrUsage::SINGLE_USE;
            }

            if(isset($input['description']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::DESCRIPTION] = $input[QrEntity::DESCRIPTION];
            }
            if(isset($input['close_by']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::CLOSE_BY] = $input[QrEntity::CLOSE_BY];
            }
            if(isset($input['name']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::NAME] = $input[QrEntity::NAME];
            }
            if(isset($input['notes']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::NOTES] = $input[QrEntity::NOTES];
            }
            if(isset($input['customer_id']) === true) {
                $input[Entity::RECEIVERS][Entity::QR_CODE][QrEntity::CUSTOMER_ID] = $input[QrEntity::CUSTOMER_ID];
            }
        }

        return $input;
    }

    protected function createSharedVirtualAccount(array $options = [])
    {
        $sharedMerchantId = $this->getDefaultMerchantId();

        $merchant = $this->repo->merchant->find($sharedMerchantId);

        $customer = (new Customer\Core)->createOrFetchSharedCustomer($merchant);

        $virtualAccount = (new Entity)->setId(Entity::SHARED_ID);

        $virtualAccount->merchant()->associate($merchant);

        $input = [
            Entity::RECEIVERS => [
                Entity::TYPES => [
                    Receiver::VPA,
                    Receiver::QR_CODE,
                    Receiver::BANK_ACCOUNT
                ]
            ],
        ];

        $input[Entity::RECEIVERS] = array_merge($input[Entity::RECEIVERS], $options);

        $virtualAccount = $this->buildVirtualAccountAndReceivers($virtualAccount, $input, $customer);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_SHARED_ACCOUNT_CREATED,
            $virtualAccount->toArrayPublic());

        return $virtualAccount;
    }

    protected function createEntityAndAssociate(Merchant $merchant)
    {
        $virtualAccount = (new Entity)->generateId();

        // We associate the merchant before building the entity, as
        // merchant billing label is used to modify the name attribute
        $virtualAccount->merchant()->associate($merchant);

        return $virtualAccount;
    }

    protected function buildReceivers(Entity $virtualAccount, array $receivers)
    {
        $virtualAccount->getValidator()->validateReceiversForBanking($receivers);

        $receiverHelper = $virtualAccount->getReceiverBuilder();

        if ($virtualAccount->isBalanceTypeBanking() === false)
        {
            $getFeatureList  = $virtualAccount->merchant->getEnabledFeatures();

            $stopQrAsReciever = $this->app->razorx->getTreatment($virtualAccount->merchant->getId(),
                                                                  RazorxTreatment::SC_STOP_QR_AS_RECEIVER_FOR_VIRTUAL_ACCOUNT,
                                                                  $this->mode);

            if (($stopQrAsReciever === 'on') and
                (in_array(Receiver::QR_CODE, $receivers[Entity::TYPES]) === true) and
                (in_array(Feature\Constants::VIRTUAL_ACCOUNTS, $getFeatureList) === true))
            {
                $feature = $this->repo->feature->findByEntityTypeEntityIdAndName(Constants::MERCHANT,
                                                                                 $virtualAccount->merchant->getId(),
                                                                                 Feature\Constants::VIRTUAL_ACCOUNTS);

                if ($feature->getCreatedAt() > Constants::QR_RECEIVER_CREATATION_STOP_TIME_STAMP)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_QR_RECEIVER_TYPE_IS_NOT_SUPPORTED);
                }
            }
        }

        foreach ($receivers[Entity::TYPES] as $receiverType)
        {
            $options = $receivers[$receiverType] ?? [];

            $this->validateReceiver($receiverType, $virtualAccount, $options);

            $func = 'build' . studly_case($receiverType);

            $receiver = $receiverHelper->$func($virtualAccount, $options);

            $association = camel_case($receiverType);

            $virtualAccount->$association()->associate($receiver);
        }

        $this->updateBalanceAccountNumberForBanking($virtualAccount);
    }

    public function getConfigsForVirtualAccount(array $receivers)
    {
        $virtualAccount = $this->createEntityAndAssociate($this->merchant);

        $vaConfig = [];

        $receiverHelper = $virtualAccount->getReceiverBuilder();

        foreach ($receivers[Entity::RECEIVER_TYPES] as $receiverType)
        {
            $receiverConfig = [];

            try
            {
                if (($receiverType === Receiver::VPA) or
                    ($receiverType === Receiver::BANK_ACCOUNT))
                {
                    $this->validateReceiver($receiverType, $virtualAccount);

                    $func = 'get' . studly_case($receiverType) . 'Configs';

                    $receiverConfig = $receiverHelper->$func($virtualAccount);
                }

                $vaConfig[$receiverType] = $receiverConfig;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }

        }

        return $vaConfig;
    }

    /**
     * Updates balance's account number if applicable per below condition.
     * @param Entity $virtualAccount
     */
    protected function updateBalanceAccountNumberForBanking(Entity $virtualAccount)
    {
        if (($virtualAccount->isBalanceTypeBanking() === true) and
            ($virtualAccount->hasBankAccount() === true))
        {
            $accountNumber = $virtualAccount->bankAccount->getAccountNumber();

            $balance = $virtualAccount->balance;

            if (empty($balance->getAccountNumber()) === true)
            {
                (new Balance\Core)->updateBalanceAccountNumber($balance, $accountNumber);
            }
        }
    }

    protected function validateReceiver(string $receiver, Entity $virtualAccount, array $options = [])
    {
        //
        // Don't validate receivers for banking virtual accounts
        // This has been taken care of previously in validateReceiversForBanking()
        //
        if ($virtualAccount->isBalanceTypeBanking() === true)
        {
            return;
        }

        switch ($receiver)
        {
            case Receiver::BANK_ACCOUNT:
                $this->verifyBankTransferEnabled($virtualAccount->merchant);
                break;

            case Receiver::QR_CODE:
                // No need to check for feature on UPI QR
                if (Receiver::isOnlyUpiQrCode($options) === false)
                {
                    $this->verifyBharatQrEnabled($virtualAccount->merchant);
                }
                break;

            case Receiver::VPA:
                $this->verifyVPAEnabled($virtualAccount->merchant);
                break;

            case Receiver::OFFLINE_CHALLAN:
                $this->verifyOfflineEnabled($virtualAccount->merchant);
                break;

            default:
                // We don't throw exception here
                // because receiver is already validated
                // and we don't want to put any validation
                // for receiver being enabled by default
                return;
        }

        if ($virtualAccount->isReceiverPresent($receiver))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_ALREADY_PRESENT);
        }
    }

    public function edit(Entity $virtualAccount, array $input)
    {
        $virtualAccount->edit($input);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    public function getDormantVaStartDate(string $startDate)
    {
        $key = self::DORMANT_VA_START_DATE . $startDate;

        $cachedStartDate = Cache::get($key);

        return (empty($cachedStartDate) === false) ? $cachedStartDate : $startDate;
    }

    public function setDormantVaStartDate(string $startDate, string $newStartDate)
    {
        $key = self::DORMANT_VA_START_DATE . $startDate;

        $ttl = self::DORMANT_VA_START_DATE_TTL;

        Cache::put($key, $newStartDate, $ttl);
    }


    public function updateStatus(Entity $virtualAccount, string $status)
    {
        $bankAccount = $virtualAccount->bankAccount;

        if (($status === Status::CLOSED) and ($bankAccount !== null))
        {
            $this->repo->deleteOrFail($bankAccount);

            $this->trace->info(TraceCode::BANK_ACCOUNT_DELETED, $bankAccount->toArray());
        }

        $virtualAccount->setStatus($status);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    public function close(Entity $virtualAccount)
    {
        $virtualAccount->getValidator()->validateOfPrimaryBalance();

        return $this->closeVA($virtualAccount);
    }

    public function closeForBanking(Entity $virtualAccount)
    {
        $virtualAccount->getValidator()->validateOfBankingBalance();

        return $this->closeVA($virtualAccount);
    }

    protected function verifyBankTransferEnabled(Merchant $merchant)
    {
        $merchantMethods = $merchant->getMethods();

        if (($merchantMethods === null) or
            ($merchantMethods->isBankTransferEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyBharatQrEnabled(Merchant $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::BHARAT_QR) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT);
        }

    }

    protected function verifyVPAEnabled(Merchant $merchant)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if (($routeName === 'virtual_account_create_for_internal') and
            ($this->app['basicauth']->isPaymentLinkServiceApp() === true))
        {
            return;
        }

        $feature = Feature\Constants::VIRTUAL_ACCOUNTS;

        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VIRTUAL_VPA_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyOfflineEnabled(Merchant $merchant)
    {

        $merchantMethods = $merchant->getMethods();

       if ($merchantMethods->isMethodEnabled('offline') === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_OFFLINE_NOT_ENABLED_FOR_MERCHANT);
        }

    }

    public function eventVirtualAccountCredited(Payment $payment)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->dispatch('api.virtual_account.credited', $eventPayload);
    }

    public function eventVirtualAccountCreated(Entity $virtualAccount)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $virtualAccount
        ];

        $this->app['events']->dispatch('api.virtual_account.created', $eventPayload);
    }

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    protected function getDefaultMerchantId()
    {
        $defaultMerchantId = Account::DEMO_PAGE_ACCOUNT;

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        return $defaultMerchantId;
    }

    public function eventVirtualAccountClosed(Entity $virtualAccount)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $virtualAccount
        ];

        $this->app['events']->dispatch('api.virtual_account.closed', $eventPayload);
    }

    public function addReceivers(Entity $virtualAccount, array $input)
    {
        $virtualAccount = Tracer::inSpan(['name' => HyperTrace::VIRTUAL_ACCOUNTS_CORE_ADD_RECEIVERS], function() use ($virtualAccount, $input) {
            return $this->repo->transaction(function () use ($virtualAccount, $input)
            {
                $this->buildReceivers($virtualAccount, $input);

                if ($this->repo->virtual_account_tpv->isTpvEnabledForVa($virtualAccount->getId()) === true)
                {
                    (new VirtualAccountTpv\Core())->validateReceiversForTpv($virtualAccount);
                }

                $this->repo->saveOrFail($virtualAccount);

                return $virtualAccount;
            });
        });

        $this->dispatchForRiskCheck($virtualAccount, $input[Entity::TYPES]);

        return $virtualAccount;
    }

    protected function closeVA(Entity $virtualAccount)
    {
        $virtualAccount = $this->repo->transaction(function () use ($virtualAccount)
        {
            $this->deactivatePayers($virtualAccount);

            $bankAccount = $virtualAccount->bankAccount;

            if ($bankAccount !== null)
            {
                $this->repo->deleteOrFail($bankAccount);

                $this->trace->info(TraceCode::BANK_ACCOUNT_DELETED, $bankAccount->toArray());
            }

            $bankAccount2 = $virtualAccount->bankAccount2;

            if ($bankAccount2 !== null)
            {
                $this->repo->deleteOrFail($bankAccount2);

                $this->trace->info(TraceCode::BANK_ACCOUNT_DELETED, $bankAccount2->toArray());
            }

            // Banking VA don't have vpa for now, but still keeping it
            $vpa = $virtualAccount->vpa;

            if ($vpa !== null)
            {
                $this->repo->deleteOrFail($vpa);

                $this->trace->info(TraceCode::VPA_DELETED, $vpa->toArray());
            }

            $virtualAccount->setStatus(Status::CLOSED);

            if ($this->isAutoCloseInactiveVirtualAccountCron() === true)
            {
                $virtualAccount->setDescriptor(Constant::DORMANT_VA_CLOSURE);
            }

            $currentTime = Carbon::now()->getTimestamp();

            $virtualAccount->setClosedAt($currentTime);

            $this->repo->saveOrFail($virtualAccount);

            $this->eventVirtualAccountClosed($virtualAccount);

            return $virtualAccount;
        });

        return $virtualAccount;
    }

    protected function isAutoCloseInactiveVirtualAccountCron()
    {
        if((app()->runningInQueue() === true) and
            (app('worker.ctx')->getJobName() === Constant::VIRTUAL_ACCOUNT_AUTO_CLOSE_INACTIVE_CRON))
        {
            return true;
        }
        return false;
    }

    protected function deactivatePayers(Entity $virtualAccount)
    {
        if ($virtualAccount->isTpvEnabled() === false)
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_NO_ALLOWED_PAYERS_TO_DEACTIVATE,
                [
                    Entity::ID      => $virtualAccount->getPublicId(),
                ]
            );

            return;
        }

        foreach ($virtualAccount->virtualAccountTpv()->get() as $virtualAccountTpv)
        {
            $this->repo->deleteOrFail($virtualAccountTpv->entity);

            (new VirtualAccountTpv\Core())->deactivate($virtualAccountTpv);
        }
    }

    protected function dispatchForRiskCheck($virtualAccount, array $receiverTypes = [])
    {
        if ($this->shouldDispatchToQueueForRiskCheck($virtualAccount->merchant) === false)
        {
            return;
        }

        $fields = [];

        foreach ($virtualAccount['receivers'] as $receiver)
        {
            $receiverType = $receiver['entity'];

            if ((empty($receiverTypes) == false) and
                (in_array($receiverType, $receiverTypes, true) === false))
            {
                continue;
            }

            $value = null;
            switch ($receiverType)
            {
                case Receiver::VPA:
                {
                    $vpaDynamic = explode('.', $receiver['username'])[1];

                    $vpaConfigs = $this->getConfigsForVirtualAccount([Entity::RECEIVER_TYPES => [$receiverType]]);

                    $merchantPrefix = explode('.', $vpaConfigs[$receiverType]['prefix'])[1];

                    $descriptor = str_replace($merchantPrefix, '', $vpaDynamic);
                    $value      = $merchantPrefix . ' ' . $descriptor;

                    break;
                }
                default:
                    break;
            }

            $this->getDedupeCheckinput(Entity::DESCRIPTOR, $value, $fields);
        }

        if (empty($fields) === false)
        {
            $this->dispatchToQueueForRiskCheck($virtualAccount, $fields);
        }
    }

    protected function shouldDispatchToQueueForRiskCheck($merchant)
    {
        $variant  = $this->app->razorx->getTreatment($merchant->getId(),
                                                     RazorxTreatment::APPS_RISK_CHECK_CREATE_VA,
                                                     $this->mode);
        if (($this->mode === Mode::TEST) or
            ($variant !== 'on') or
            ($merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK) === true))
        {
            return false;
        }

        return true;
    }

    protected function getDedupeCheckinput($key, $value, & $fields)
    {
        if ($value === null)
        {
            return;
        }

        $checkLists = [Constants::HIGH_RISK_LIST, Constants::BRAND_LIST, Constants::AUTHORITIES_LIST];

        foreach ($checkLists as $checkList)
        {
            $field = [
                'key'        => $key,
                'value'      => $value,
                'list'       => $checkList,
                'config_key' => $key,
            ];
            array_push($fields, $field);
        }
    }

    protected function dispatchToQueueForRiskCheck($virtualAccount, array $fields)
    {
        $request = [
            'entity_type' => $virtualAccount->getEntity(),
            'merchant_id' => $virtualAccount->getMerchantId(),
            'client_type' => 'smart_collect',
            'entity_id'   => $virtualAccount->getId(),
            'fields'      => $fields,
            'checks'      => ['risk_factor']
        ];

        try
        {
            $this->trace->info(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_INIT,
                $request);

            AppsRiskCheck::dispatch($this->mode, $request);
        }
        catch (\Exception $e)
        {
            $this->trace->critical(
                TraceCode::APPS_RISK_CHECK_SQS_PUSH_FAILED,
                $request);
        }
    }
}
