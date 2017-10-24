<?php

namespace RZP\Models\Payment;

use DB;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Constants\Table;
use RZP\Models\Customer\Token;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Verify;
use RZP\Models\Transaction;
use RZP\Models\Invoice;
use RZP\Base\BuilderEx;

class Repository extends Base\Repository
{
    protected $entity = 'payment';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::EMAIL              => 'sometimes|email',
        Entity::ORDER_ID           => 'sometimes|string|size:20',
        Entity::TRANSFERRED        => 'sometimes|boolean|in:0,1',
        self::EXPAND . '.*'        => 'string|in:card,',
    ];

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::EMAIL              => 'sometimes',
        Entity::STATUS             => 'sometimes|string',
        Entity::NOTES              => 'sometimes|string|max:500',
        Entity::INVOICE_ID         => 'sometimes|string|min:14|max:18',
        Entity::SUBSCRIPTION_ID    => 'sometimes|string|min:14|max:18',
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::STATUS             => 'sometimes|string',
        Entity::VERIFIED           => 'sometimes|in:null,0,1,2',
        Entity::REFUND_STATUS      => 'sometimes|in:null,partial,full',
        Entity::TWO_FACTOR_AUTH    => 'sometimes|string',
        Entity::BANK               => 'sometimes',
        Entity::METHOD             => 'sometimes',
        Entity::GATEWAY            => 'sometimes',
        Entity::EMAIL              => 'sometimes|email',
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::TRANSFER_ID        => 'sometimes|alpha_num|size:14',
        Entity::CARD_ID            => 'sometimes|alpha_num|size:14',
        Entity::CAPTURED           => 'sometimes|in:0,1',
        Entity::WALLET             => 'sometimes|custom',
        Entity::NOTES              => 'sometimes|notes_fetch',
        Card\Entity::IIN           => 'sometimes|integer|digits:6',
        Card\Entity::LAST4         => 'sometimes|string|digits:4',
        Card\Entity::INTERNATIONAL => 'sometimes|in:0,1',
        Entity::CUSTOMER_ID        => 'sometimes|alpha_num|size:14',
        Entity::TOKEN_ID           => 'sometimes|alpha_num|size:14',
        Entity::GLOBAL_TOKEN_ID    => 'sometimes|alpha_num|size:14',
        Entity::SAVE               => 'sometimes|in:0,1',
        Entity::LATE_AUTHORIZED    => 'sometimes|in:0,1',
        Entity::AMOUNT             => 'sometimes|integer',
        Entity::TERMINAL_ID        => 'sometimes|alpha_num|size:14',
    ];

    protected $signedIds = [
        Entity::ORDER_ID,
        Entity::INVOICE_ID,
        Entity::SUBSCRIPTION_ID,
    ];

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Entity::PAYMENT_WINDOW;

        $pid = $this->dbColumn(Payment\Entity::ID);
        $paPaymentId = $this->repo
                            ->payment_analytics
                            ->dbColumn(Analytics\Entity::PAYMENT_ID);

        $paymentColumns = $this->dbColumn('*');

        $paTable = $this->repo->payment_analytics->getTableName();
        $checkoutIdAttr = $this->repo
                               ->payment_analytics
                               ->dbColumn(Analytics\Entity::CHECKOUT_ID);

        return $this->newQuery()
                    ->select($paymentColumns)
                    ->join($paTable, $pid, '=', $paPaymentId)
                    ->where($checkoutIdAttr, '=', $checkoutId)
                    ->createdAtGreaterThan($timestamp)
                    ->latest()
                    ->get();
    }

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        return $this->newQuery()
                    ->whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->status(Payment\Status::CAPTURED)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    public function fetchPaymentsWithStatus($from, $to, $gateway, $status)
    {
        return $this->newQuery()
                    ->whereBetween(Payment\Entity::AUTHORIZED_AT, array($from, $to))
                    ->whereIn('status', $status)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    /**
     * Returns the captured payments
     * between the given timestamps (using CAPTURED_AT)
     * @param  int $from    timestamp for start of interval
     * @param  int $to      timestamp for end of interval
     * @return Collection of Payment
     */
    public function fetchCapturedBetweenTimestamp($from, $to, $merchantId)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function fetchEmiPaymentsWithCardTerminalsBetween($from, $to, $bank)
    {
        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        return $this->newQuery()
                    ->join($tTableName, $paymentTerminalId, '=', $terminalId)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->where(Entity::BANK, '=', $bank)
                    ->where(Entity::METHOD, '=', Method::EMI)
                    ->where($terminalEmi, '=', false)
                    ->with('card.globalCard')
                    ->with('emiPlan')
                    ->select($paymentData)
                    ->get();
    }

    public function fetchCreatedPaymentsWithInternalError($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->whereNotNull(Payment\Entity::INTERNAL_ERROR_CODE)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->get();
    }

    /**
     * Fetches entity with given id with a mysql lock for update
     *
     * @param string       $id
     * @param bool|boolean $withTrashed
     *
     * withTrashed: Method signature changed to make it compatible
     *              with Base/Repository's method.
     *
     * @return Entity
     */
    public function lockForUpdate(string $id, bool $withTrashed = false)
    {
        return $this->newQuery()
                    ->lockForUpdate()->findOrFail($id);
    }

    public function timeoutOldPayments($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->whereNull(Payment\Entity::INTERNAL_ERROR_CODE)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->update(
                        array(
                            Payment\Entity::STATUS => Payment\Status::FAILED,
                            Payment\Entity::ERROR_CODE => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                            Payment\Entity::ERROR_DESCRIPTION => PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT)
                        );
    }

    /**
     * Fetches old payments which can be timed-out with respective
     * merchant relation.
     */
    public function fetchOldCreatedPaymentsForTimeout($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->with(['merchant', 'merchant.features'])
                    ->get();
    }

    /**
     * This function is used to fetch the authorized payments where
     * Merchant auto refund delay is null.
     *
     * @param int  $timestamp
     * @param bool $getDisputed Flag to check whether to get disputed payments
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsBeforeTimestamp(int $timestamp, bool $getDisputed = true): Base\PublicCollection
    {
        $createdAt  = $this->dbColumn(Entity::CREATED_AT);
        $merchantId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $query = $this->newQuery()
                      ->select($this->dbColumn('*'))
                      ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                      ->whereNull(Merchant\Entity::AUTO_REFUND_DELAY)
                      ->status(Payment\Status::AUTHORIZED)
                      ->where($createdAt, '<=', $timestamp)
                      ->orderBy(Payment\Entity::MERCHANT_ID);

        // Check if we should pick disputed payments for refund
        if ($getDisputed === false)
        {
            $query = $query->where(Entity::DISPUTED, '=', 0);
        }

        return $query->get();
    }

    /**
     * This function is used to fetch the authorized payments
     * that are not disputed with merchant auto delay delay
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsWithAutoRefundDelay()
    {
        $paymentCreatedAt = $this->dbColumn(Entity::CREATED_AT);
        $merchantId       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $minCreatedAt = Carbon::now()->subSeconds(Merchant\Entity::MIN_AUTO_REFUND_DELAY)->getTimestamp();

        $rawCondition = '(' . time() . ' - ' . $paymentCreatedAt . ') > ' . Merchant\Entity::AUTO_REFUND_DELAY;

        return $this->newQuery()
                    ->select($this->dbColumn('*'))
                    ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                    ->status(Payment\Status::AUTHORIZED)
                    ->whereRaw($rawCondition)
                    ->whereNotNull(Merchant\Entity::AUTO_REFUND_DELAY)
                    ->where($paymentCreatedAt, '<', $minCreatedAt)
                    ->where(Entity::DISPUTED, '=', 0)
                    ->get();
    }

    public function getAuthorizedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        return $this->newQuery()
                    ->status(Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CREATED_AT, '>', $timeLowerLimit)
                    ->get();
    }

    public function getAutoCapturedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CAPTURED)
                    ->where(Payment\Entity::AUTO_CAPTURED, '=', true)
                    ->where(Payment\Entity::CAPTURED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CAPTURED_AT, '>', $timeLowerLimit)
                    ->orderBy(Payment\Entity::MERCHANT_ID, 'desc')
                    ->orderBy(Payment\Entity::ID, 'desc')
                    ->get();
    }

    /**
     * Return Payments object(s) which should be verified
     *
     * @param array        $minMaxArray      Min/Max array
     * @param array|string $verifyBoundary   Array of [VERIFY_BUCKET and timestamp] values
     * @param string       $verifyStatus     Value for filter of VerifyStatus
     * @param string       $paymentStatus    Value for filter of paymentStatus
     * @param int          $rowsToFetch      Rows to fetch
     * @param array        $disabledGateways Gateways for which verify should be skipped
     * @param bool         $random           Db should take param in random value or not
     *
     * @return array
     */
    public function getPaymentsToVerify(
                        array $minMaxArray,
                        array $verifyBoundary,
                        $verifyStatus = null,
                        $paymentStatus = null,
                        int $rowsToFetch = 100,
                        array $disabledGateways = [],
                        bool $random = true)
    {
        $query = $this->newQuery()
                      ->whereNotNull(Payment\Entity::GATEWAY)
                      ->whereNotIn(
                          Payment\Entity::GATEWAY,
                          $disabledGateways);

        if ($verifyStatus !== null)
        {
            $query->where(Payment\Entity::VERIFIED, '=', $verifyStatus);
        }

        if ($paymentStatus !== null)
        {
            $query->status($paymentStatus);
        }

        if ($random === true)
        {
            $query->inRandomOrder();
        }

        // For verify Error, we only look at the payment status.
        if ($verifyStatus !== Verify\Status::ERROR)
        {
            $this->addWhereConditionsUsingVerifyBoundary($minMaxArray, $verifyBoundary, $query);
        }
        else
        {
            $this->addWhereConditionsUsingMinimumTime($minMaxArray, $query);
        }

        // Sample Query
        // SELECT *
        // FROM   `payments`
        // WHERE  `gateway` NOT IN ( 'wallet_openwallet' )
        //        AND `status` = 'failed'
        //        AND ( ( `verify_bucket` = '0' AND `created_at` < '1478023148' )
        //              OR ( `verify_bucket` = '1' AND `created_at` < '1478022368' )
        //              OR ( `verify_bucket` = '2' AND `created_at` < '1478019668' )
        //              OR ( `verify_bucket` = '3' AND `created_at` < '1477936868' )
        //              OR ( `verify_bucket` = '4' AND `created_at` < '1477850468' )
        //              OR ( `verify_bucket` = '5' AND `created_at` < '1477764068' )
        //              OR ( `verify_bucket` = '6' AND `created_at` < '1477677668' )
        //              OR ( `verify_bucket` = '7' AND `created_at` < '1477591268' )
        //              OR ( `verify_bucket` = '8' AND `created_at` < '1477504868' )
        //              OR ( `verify_bucket` = '9' AND `created_at` < '1477418468' )
        //            )
        // ORDER  BY Rand()
        // LIMIT  100

        // We want total number of Payments which are awaiting verify, for logging
        $verifiableCount = $query->count();

        $payments = $query->take($rowsToFetch)
                          ->with('merchant')
                          ->get();

        return ['payments' => $payments, 'verifiable_count' => $verifiableCount];
    }

    /**
     * Add Where Condition for Created Payments, And Verify Failed Payments
     *
     * @param array     $minMaxArray Min Max array to filter payments created $ts sec before and $tx time after
     * @param BuilderEx $query       original query
     *
     * @return void
     */
    protected function addWhereConditionsUsingMinimumTime(array $minMaxArray, BuilderEx $query)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $query->where(Payment\Entity::CREATED_AT, '<=', $currentTime - $minMaxArray['min']);
    }

    protected function addWhereClauseForMinAndMaxTime(array $minMaxArray, array & $whereConditions)
    {
        $currentTime = Carbon::now()->getTimestamp();

        if ($minMaxArray['max'] !== null)
        {
            $whereConditions[] = [
                [Payment\Entity::CREATED_AT, '<=', $currentTime - $minMaxArray['min']],
                [Payment\Entity::CREATED_AT, '>=', $currentTime - $minMaxArray['max']]
            ];
        }
    }

    /**
     * Process min_time and verify_boundary array and return where and orWhere Condition
     *
     * @param array     $minMaxArray      Min Max array to filter payments created $ts sec before and $tx time after
     * @param array     $verifyBoundaries array with Key as bucket and value as time for that bucket
     * @param BuilderEx $query            original query
     *
     * @return void
     */
    protected function addWhereConditionsUsingVerifyBoundary(
                                                    array $minMaxArray,
                                                    array $verifyBoundaries,
                                                    BuilderEx $query)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $whereConditions = [];

        $this->addWhereClauseForMinAndMaxTime($minMaxArray, $whereConditions);

        // Each or condition will fetch payments which are
        // in next Verify Bucket and not processed by previous cron
        // This will not give all payments at once, but only payments which
        // crossed the boundary after prev cron ran (SLIDING WINDOW PROTOCOL)
        foreach ($verifyBoundaries as $bucket => $time)
        {
            // This gets all the payments in the last `boundary (15, 60, etc)` time.
            // $boundary has time in seconds, signifying payment should be X second old
            // For querying on db, need to change that to absolute value
            $paymentCreatedAfter = $currentTime - $time;

            $whereConditions[] = [
                [Payment\Entity::VERIFY_BUCKET, '=', ($bucket + 1)],
                [Payment\Entity::CREATED_AT, '<', $paymentCreatedAfter]
            ];
        }

        // Now add the conditions to the payment verify query.
        $query->where(
            function ($query) use ($whereConditions)
            {
                foreach($whereConditions as $condition)
                {
                    $query->orWhere($condition);
                }
            });
    }

    public function fetchPaymentsForCustomerMethod($customer, $method, $skip)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::METHOD, '=', $method)
                    ->where(Payment\Entity::GLOBAL_CUSTOMER_ID, '=', $customer->getId())
                    ->whereNotNull(Payment\Entity::CAPTURED_AT)
                    ->skip($skip)
                    ->take(10)
                    ->with('merchant', 'card')
                    ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, $count, $skip, $relations);
    }

    public function fetchReconciledPaymentsForGateway($from, $to, $gateway, $status)
    {
        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);

        $txnRepo = $this->repo->transaction;

        $transactionPaymentId = $txnRepo->dbColumn(Transaction\Entity::ENTITY_ID);

        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);

        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        return $this->newQuery()
                    ->select($paymentAttrs)
                    ->join($txnRepo->getTableName(), $paymentId, '=', $transactionPaymentId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where($transactionEntityType, '=', 'payment')
                    ->whereBetween($transactionReconciledAt, [$from, $to])
                    ->whereIn(Entity::STATUS, $status)
                    ->get();
    }

    public function fetchReconciledPaymentsForTpv($from, $to, $gateway, $status, $tpvEnabled = false)
    {
        // SELECT `payments`.*
        // FROM `payments`
        // INNER JOIN `transactions` ON `payments`.`id` = `transactions`.`entity_id`
        // INNER JOIN `terminals` ON `payments`.`terminal_id` = `terminals`.`id`
        // WHERE `payments`.`gateway` = $gateway
        //   AND `transactions`.`type` = 'payment'
        //   AND `transactions`.`reconciled_at` BETWEEN $from AND $to
        //   AND `payments`.`status` IN ( $status ) // status is an array
        //   AND `terminals`.`tpv` = $tpvEnabled

        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);
        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);
        $paymentGateway = $this->dbColumn(Entity::GATEWAY);
        $paymentStatus = $this->dbColumn(Entity::STATUS);

        $txnRepo = $this->repo->transaction;

        $tRepo = $this->repo->terminal;
        $tTableName = $tRepo->getTableName();

        $transactionPaymentId = $txnRepo->dbColumn(Transaction\Entity::ENTITY_ID);
        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);
        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);
        $terminalTpv = $tRepo->dbColumn(Terminal\Entity::TPV);

        return $this->newQuery()
                    ->select($paymentAttrs)
                    ->join($txnRepo->getTableName(), $paymentId, '=', $transactionPaymentId)
                    ->join($tRepo->getTableName(), $paymentTerminalId, '=', $terminalId)
                    ->where($paymentGateway, '=', $gateway)
                    ->where($transactionEntityType, '=', 'payment')
                    ->whereBetween($transactionReconciledAt, [$from, $to])
                    ->whereIn($paymentStatus, $status)
                    ->where($terminalTpv, '=', $tpvEnabled)
                    ->get();
    }

    public function fetchPaymentsForOrderId($orderId)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                    ->get();
    }

    /**
     * Fetches all payments for on hold flag update with on_hold_until
     * timestamp earlier than timestamp parameter.
     *
     * @param int $timestamp
     * @return Base\PublicCollection
     */
    public function getPaymentsOnHoldBeforeTimestamp(int $timestamp) : Base\PublicCollection
    {
        $data = $this->newQuery()
                     ->where(Payment\Entity::ON_HOLD, true)
                     ->where(Payment\Entity::ON_HOLD_UNTIL, '<', $timestamp)
                     ->with('transfer')
                     ->limit(500)
                     ->get();

        return $data;
    }

    protected function addQueryParamBank($query, $params)
    {
        if (Payment\Processor\Netbanking::isSupportedBank($params['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                Entity::BANK);
        }

        $query = $query->where(Entity::BANK, '=', $params[Entity::BANK]);
    }

    protected function addQueryParamStatus($query, $params)
    {
        $status = $params[Entity::STATUS];

        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $query->whereIn(Entity::STATUS, $status);
    }

    protected function addQueryParamAmount($query, $params)
    {
        $amount = $this->dbColumn(Entity::AMOUNT);

        $query->where($amount, '=', $params[Entity::AMOUNT]);
    }

    protected function addQueryParamIin($query, $params)
    {
        $this->joinQueryCard($query);

        $query->where(Card\Entity::IIN, '=', $params[Card\Entity::IIN]);

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamLast4($query, $params)
    {
        $this->joinQueryCard($query);

        $query->where(Card\Entity::LAST4, '=', $params[Card\Entity::LAST4]);

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamInternational($query, $params)
    {
        $international = $this->dbColumn(Entity::INTERNATIONAL);

        $query->where($international, '=', $params[Entity::INTERNATIONAL]);
    }

    /**
     * Param to filter payments that have been transferred (amount_transferred > 0)
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamTransferred($query, $params)
    {
        if ($params[Entity::TRANSFERRED] !== '1')
        {
            return;
        }

        $amountTransferred = $this->dbColumn(Entity::AMOUNT_TRANSFERRED);

        $query->where($amountTransferred, '>', 0);
    }

    protected function addQueryCaptured($query, $params)
    {
        $captured = $params[Entity::CAPTURED];

        if ($captured === '0')
        {
            $query->whereNull(Entity::CAPTURED_AT);
        }
        else
        {
            $query->whereNotNull(Entity::CAPTURED_AT);
        }
    }

    protected function addQueryParamEmail($query, $params)
    {
        $merchant = $this->auth->getMerchant();

        if (($this->auth->isPrivateAuth() === true) and
            ($this->auth->isProxyAuth() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::PAYMENT_EMAIL_FETCH) === false))
        {
            throw new Exception\ExtraFieldsException('email');
        }

        return parent::addQueryParamEmail($query, $params);
    }

    protected function joinQueryCard($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ? $joins : [];

        foreach ($joins as $join)
        {
            if ($join->table === $this->repo->card->getTableName())
            {
                return;
            }
        }

        $paymentCardId = $this->dbColumn(Payment\Entity::CARD_ID);
        $cardId = $this->repo->card->dbColumn(Card\Entity::ID);

        $query->join($this->repo->card->getTableName(), $paymentCardId, '=', $cardId);
    }

    public function getYesterdayVolume()
    {
        $yesterday = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $today = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->getPaymentVolumeBetweenTimestamp($yesterday, $today);
    }

    public function getCurrentMonthVolume()
    {
        $from = Carbon::yesterday(Timezone::IST)->startOfMonth()->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->getPaymentVolumeBetweenTimestamp($from, $to);
    }

    public function getCreatedAndFailedPaymentsForOrder($orderId)
    {
        $ts = time() - Payment\Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->whereIn(Entity::STATUS, [Status::CREATED, Status::FAILED])
                    ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                    ->where(Payment\Entity::CREATED_AT, '>', $ts)
                    ->get();
    }

    public function getCapturedPaymentForOrder(string $orderId)
    {
        return $this->newQuery()
                    ->whereNotNull(Entity::CAPTURED_AT)
                    ->where(Entity::ORDER_ID, '=', $orderId)
                    ->first();
    }

    public function getYesterdayTopMerchantVolumeWise()
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp();

        $pid = $this->dbColumn(Payment\Entity::MERCHANT_ID);
        $mid = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        return $this->newQuery()
                    ->join($this->repo->merchant->getTableName(), $pid, '=', $mid)
                    ->selectRaw(
                       Payment\Entity::MERCHANT_ID . ','.
                       Merchant\Entity::NAME . ','.
                       Merchant\Entity::WEBSITE . ','.
                       "SUM(amount) / 100 AS volume" . ','.
                       'COUNT(*) AS count')
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->groupBy(
                        Payment\Entity::MERCHANT_ID,
                        Merchant\Entity::NAME,
                        Merchant\Entity::WEBSITE)
                    ->orderBy('volume', 'desc')
                    ->limit(75)
                    ->get();
    }

    public function getMonthTopMerchantVolumeWise()
    {
        $from = Carbon::yesterday(Timezone::IST)->startOfMonth()->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp();

        $pid = $this->dbColumn(Payment\Entity::MERCHANT_ID);
        $mid = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        return $this->newQuery()
                    ->join($this->repo->merchant->getTableName(), $pid, '=', $mid)
                    ->selectRaw(
                       Payment\Entity::MERCHANT_ID . ','.
                       Merchant\Entity::NAME . ','.
                       Merchant\Entity::WEBSITE . ','.
                       "SUM(amount) / 100 AS volume" . ','.
                       'COUNT(*) AS count')
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->groupBy(
                        Payment\Entity::MERCHANT_ID,
                        Merchant\Entity::NAME,
                        Merchant\Entity::WEBSITE)
                    ->orderBy('volume', 'desc')
                    ->limit(75)
                    ->get();
    }

    public function fetchAuthorizedSummary()
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::AUTHORIZED)
                    ->groupBy(Entity::MERCHANT_ID)
                    ->selectRaw(Entity::MERCHANT_ID . ','.
                       'SUM(' . Entity::AMOUNT . ') AS sum' . ','.
                       'COUNT(*) AS count')
                    ->get();
    }

    public function findByTransferIdAndMerchant(string $transferId, string $accountId)
    {
        return $this->newQuery()
                    ->where(Entity::TRANSFER_ID, $transferId)
                    ->merchantId($accountId)
                    ->firstOrFailPublic();
    }

    public function fetchCapturedSummaryBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->groupBy(Entity::MERCHANT_ID)
                    ->selectRaw(Entity::MERCHANT_ID . ','.
                       'SUM(' . Entity::AMOUNT . ') AS sum' . ','.
                       'COUNT(*) AS count')
                    ->get();
    }

    /**
     * Fetches the number of times a payment has been made against each offer id in $offerIds
     * grouped by offerId
     *
     * @param  array $cardIds  Card ids to check
     * @param  array $offerIds Offer ids to check
     *
     * @return array Count of payments
     */
    public function getPaymentCountForCardIdsAndOfferIds(array $cardIds, array $offerIds): array
    {
        $ordersTable = $this->repo->order->getTableName();
        $paymentOrderIdCol = $this->dbColumn(Entity::ORDER_ID);
        $orderIdCol = $this->repo->order->dbColumn(Order\Entity::ID);
        $paymentStatusCol = $this->dbColumn(Entity::STATUS);
        $orderOfferIdCol = $this->repo->order->dbColumn(Order\Entity::OFFER_ID);
        $paymentCardIdCol = $this->dbColumn(Entity::CARD_ID);
        $paymentIdCol = $this->dbColumn(Entity::ID);

        // Query executed - select count(payments.id) AS payment_count, orders.offer_id
        // from `payments` inner join `orders` on `payments`.`order_id` = `orders`.`id`
        // where `payments`.`status` = ? and `orders`.`offer_id` in (?) and `payments`.`card_id`
        // in (?) having payment_count >= 1 group by `orders`.`offer_id`
        return $this->newQuery()
                    ->select(DB::raw("count($paymentIdCol) AS payment_count, $orderOfferIdCol"))
                    ->join($ordersTable, $paymentOrderIdCol, '=', $orderIdCol)
                    ->where($paymentStatusCol, '=', Status::CAPTURED)
                    ->whereIn($orderOfferIdCol, $offerIds)
                    ->whereIn($paymentCardIdCol, $cardIds)
                    ->groupBy($orderOfferIdCol)
                    ->having('payment_count', '>=', 1)
                    ->pluck('payment_count', 'offer_id')
                    ->toArray();
    }

    public function fetchBankTransferPaymentsByPublicVaIdAndMerchant(
        string $virtualAccountId,
        Merchant\Entity $merchant
        )
    {
        $paymentId = $this->dbColumn(Payment\Entity::ID);
        $paymentMethod = $this->dbColumn(Payment\Entity::METHOD);
        $paymentMerchantId = $this->dbColumn(Payment\Entity::MERCHANT_ID);

        $paymentColumns = $this->dbColumn('*');

        $bankTransferTable = $this->repo->bank_transfer->getTableName();

        $bankTransferPaymentId = $this->repo
                                      ->bank_transfer
                                      ->dbColumn(BankTransfer\Entity::PAYMENT_ID);

        $bankTransferVirtualAccountId = $this->repo
                                             ->bank_transfer
                                             ->dbColumn(BankTransfer\Entity::VIRTUAL_ACCOUNT_ID);

        VirtualAccount\Entity::verifyIdAndSilentlyStripSign($virtualAccountId);

        return $this->newQuery()
                    ->select($paymentColumns)
                    ->join($bankTransferTable, $paymentId, '=', $bankTransferPaymentId)
                    ->where($bankTransferVirtualAccountId, '=', $virtualAccountId)
                    ->where($paymentMerchantId, '=', $merchant->getId())
                    ->where($paymentMethod, '=', Method::BANK_TRANSFER)
                    ->orderByCreatedAt()
                    ->get();
    }

    /**
     * Gets all authorized payments which belongs to a
     * paid order and are not disputed. All these
     * payments are supposed to be refunded.
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsOfPaidOrderForRefund()
    {
        // Raw SQL:
        //
        // SELECT payments.*
        // FROM payments
        //     INNER JOIN orders on orders.id = payments.order_id
        // WHERE payments.created_at > ?
        //     AND orders.status = 'PAID'
        //     AND payments.status = 'AUTHORIZED'

        $orderTable  = $this->repo->order->getTableName();
        $orderId     = $this->repo->order->dbColumn(Order\Entity::ID);
        $orderStatus = $this->repo->order->dbColumn(Order\Entity::STATUS);

        $paymentCols      = $this->dbColumn('*');
        $paymentStatus    = $this->dbColumn(Entity::STATUS);
        $paymentDisputed  = $this->dbColumn(Entity::DISPUTED);
        $paymentOrderId   = $this->dbColumn(Entity::ORDER_ID);
        $paymentCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        // For optimization purposes we only pick payments in last 10 days. This picked
        // '10 days' is sufficient filter logically.

        $nowMinus10Days = Carbon::today(Timezone::IST)->subDays(10)->getTimestamp();

        $results = $this->newQuery()
                        ->join($orderTable, $orderId, '=', $paymentOrderId)
                        ->select($paymentCols)
                        ->where($paymentCreatedAt, '>', $nowMinus10Days)
                        ->where($orderStatus, Order\Status::PAID)
                        ->where($paymentStatus, Status::AUTHORIZED)
                        ->where($paymentDisputed, 0)
                        ->with('merchant')
                        ->get();

        return $results;
    }

    protected function getPaymentVolumeBetweenTimestamp($from, $to)
    {
        $vol = $this->newQuery()
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->selectRaw('SUM(' . Entity::AMOUNT . ') AS amount' . ','.
                       'COUNT(*) AS count')
                    ->where(Entity::METHOD, '!=', Method::TRANSFER)
                    ->first();

        return $vol;
    }

    protected function validateWallet($attribute, $value)
    {
        Processor\Wallet::validateExists($value);
    }

    public function getTotalUsedCountForTerminal($terminalId)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::TERMINAL_ID, '=', $terminalId)
                    ->count();
    }

    public function getCapturedAmountByGateway(string $gateway, int $from, int $to)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->sum(Entity::AMOUNT);
    }

    public function updateTax(int $limit = 10000)
    {
        return $this->newQuery()
                    ->whereNull(Entity::TAX)
                    ->whereNotNull(Entity::SERVICE_TAX)
                    ->limit($limit)
                    ->update([Entity::TAX => DB::raw(Entity::SERVICE_TAX)]);
    }

    public function fetchPendingEMandateRegistration(string $gateway, int $from, int $to)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Token\Entity::RECURRING);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($selectCols)
                    ->join(
                          Table::TOKEN,
                          function ($join)
                          use($tokenIdColumn)
                            {
                                $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                                $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                            })
                    ->where(Entity::RECURRING_TYPE, '=', RecurringType::INITIAL)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::NETBANKING)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                    ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::INITIATED)
                    ->where($tokenRecurringColumn, '!=', 1)
                    ->with(['localToken', 'globalToken', 'customer'])
                    ->get();
    }

    public function fetchPendingEMandateDebit(string $gateway, $from, $to)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Token\Entity::RECURRING);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($selectCols)
                    ->join(
                        Table::TOKEN,
                        function ($join)
                        use ($tokenIdColumn)
                        {
                          $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                          $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                        })
                    ->where(Entity::RECURRING_TYPE, '=', RecurringType::AUTO)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::NETBANKING)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                    ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::CONFIRMED)
                    ->where($tokenRecurringColumn, '=', 1)
                    ->with(['localToken', 'globalToken', 'merchant', 'order'])
                    ->get();
    }
}
