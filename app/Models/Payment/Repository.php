<?php

namespace RZP\Models\Payment;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Terminal;
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
    ];

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::EMAIL              => 'sometimes',
        Entity::STATUS             => 'sometimes|string',
        Entity::NOTES              => 'sometimes|string|max:500',
        Entity::INVOICE_ID         => 'sometimes|string|max:18',
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
        Entity::CARD_ID            => 'sometimes|alpha_num|size:14',
        Entity::CAPTURED           => 'sometimes|in:0,1',
        Entity::WALLET             => 'sometimes|custom',
        Entity::NOTES              => 'sometimes|string|max:500',
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

    protected $esWhitelistedParams = [
        Entity::NOTES
    ];

    protected $signedIds = [
        Entity::ORDER_ID,
        Entity::INVOICE_ID,
    ];

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Entity::PAYMENT_WINDOW;

        $pid = $this->getAttributeWithTableName(Payment\Entity::ID);
        $paPaymentId = $this->manager
                            ->payment_analytics
                            ->getAttributeWithTableName(Analytics\Entity::PAYMENT_ID);

        $paymentColumns = $this->getAttributeWithTableName('*');

        $paTable = $this->manager->payment_analytics->getTableName();
        $checkoutIdAttr = $this->manager
                               ->payment_analytics
                               ->getAttributeWithTableName(Analytics\Entity::CHECKOUT_ID);

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

    public function fetchEmiPaymentsBetween($from, $to, $bank)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::UPDATED_AT, [$from, $to])
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->where(Entity::BANK, '=', $bank)
                    ->where(Entity::METHOD, '=', Method::EMI)
                    ->with('card.globalCard')
                    ->with('emiPlan')
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
     * @param $timestamp
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsBeforeTimestamp($timestamp)
    {
        $createdAt  = $this->getAttributeWithTableName(Entity::CREATED_AT);
        $merchantId = $this->manager->merchant->getAttributeWithTableName(Merchant\Entity::ID);

        return $this->newQuery()
                    ->select($this->getAttributeWithTableName('*'))
                    ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereNull(Merchant\Entity::AUTO_REFUND_DELAY)
                    ->status(Payment\Status::AUTHORIZED)
                    ->where($createdAt, '<=', $timestamp)
                    ->orderBy(Payment\Entity::MERCHANT_ID)
                    ->get();
    }

    /**
     * This function is used to fetch the authorized payments with
     * merchant auto delay delay
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsWithAutoRefundDelay()
    {
        $paymentCreatedAt = $this->getAttributeWithTableName(Entity::CREATED_AT);
        $merchantId       = $this->manager->merchant->getAttributeWithTableName(Merchant\Entity::ID);

        $minCreatedAt = Carbon::now()->subMinutes(30)->timestamp;
        $maxCreatedAt = Carbon::now()->subDays(7)->timestamp;

        $rawCondition = '(' . time() . ' - ' . $paymentCreatedAt . ') > ' . Merchant\Entity::AUTO_REFUND_DELAY;

        return $this->newQuery()
                    ->select($this->getAttributeWithTableName('*'))
                    ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                    ->status(Payment\Status::AUTHORIZED)
                    ->whereRaw($rawCondition)
                    ->whereNotNull(Merchant\Entity::AUTO_REFUND_DELAY)
                    ->where($paymentCreatedAt, '<', $minCreatedAt)
                    ->where($paymentCreatedAt, '>=', $maxCreatedAt)
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
     * @param array  $minMaxArray    Min/Max array
     * @param string $verifyBoundary array of [VERIFY_BUCKET and timestamp] values
     * @param string $verifyStatus   value for filter of VerifyStatus
     * @param string $paymentStatus  value for filter of paymentStatus
     * @param bool   $random         Db should take param in random value or not
     * @param int    $rowsToFetch    Rows to fetch
     *
     * @return Collection of Payment
     */
    public function getPaymentsToVerify(
                        array $minMaxArray,
                        array $verifyBoundary,
                        $verifyStatus = null,
                        $paymentStatus = null,
                        bool $random = true,
                        int $rowsToFetch = 100)
    {
        $verifyEnabledGateways = Payment\Gateway::$verifyEnabled;

        $query = $this->newQuery()
                      ->whereIn(Payment\Entity::GATEWAY, $verifyEnabledGateways);

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
        // WHERE  `gateway` IN ( 'axis_migs', 'billdesk', 'ebs', 'mobikwik',
        //                      'paytm', 'hdfc', 'amex', 'netbanking_hdfc',
        //                      'netbanking_kotak', 'wallet_payzapp', 'first_data',
        //                      'cybersource', 'wallet_payumoney', 'wallet_airtelmoney',
        //                      'wallet_olamoney', 'wallet_freecharge' )
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
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        $query->where(Payment\Entity::CREATED_AT, '<=', $currentTime - $minMaxArray['min']);
    }

    protected function addWhereClauseForMinAndMaxTime(array $minMaxArray, array & $whereConditions)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

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
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

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
        $paymentAttrs = $this->getAttributeWithTableName('*');

        $paymentId = $this->getAttributeWithTableName(Entity::ID);

        $txnRepo = $this->manager->transaction;

        $transactionPaymentId = $txnRepo->getAttributeWithTableName(Transaction\Entity::ENTITY_ID);

        $transactionEntityType = $txnRepo->getAttributeWithTableName(Transaction\Entity::TYPE);

        $transactionReconciledAt = $txnRepo->getAttributeWithTableName(Transaction\Entity::RECONCILED_AT);

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

        $paymentAttrs = $this->getAttributeWithTableName('*');

        $paymentId = $this->getAttributeWithTableName(Entity::ID);
        $paymentTerminalId = $this->getAttributeWithTableName(Entity::TERMINAL_ID);
        $paymentGateway = $this->getAttributeWithTableName(Entity::GATEWAY);
        $paymentStatus = $this->getAttributeWithTableName(Entity::STATUS);

        $txnRepo = $this->manager->transaction;

        $tRepo = $this->manager->terminal;
        $tTableName = $tRepo->getTableName();

        $transactionPaymentId = $txnRepo->getAttributeWithTableName(Transaction\Entity::ENTITY_ID);
        $transactionEntityType = $txnRepo->getAttributeWithTableName(Transaction\Entity::TYPE);
        $transactionReconciledAt = $txnRepo->getAttributeWithTableName(Transaction\Entity::RECONCILED_AT);

        $terminalId = $tRepo->getAttributeWithTableName(Terminal\Entity::ID);
        $terminalTpv = $tRepo->getAttributeWithTableName(Terminal\Entity::TPV);

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
        $amount = $this->getAttributeWithTableName(Entity::AMOUNT);

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
        $international = $this->getAttributeWithTableName(Entity::INTERNATIONAL);

        $query->where($international, '=', $params[Entity::INTERNATIONAL]);
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
            if ($join->table === $this->manager->card->getTableName())
            {
                return;
            }
        }

        $paymentCardId = $this->getAttributeWithTableName(Payment\Entity::CARD_ID);
        $cardId = $this->manager->card->getAttributeWithTableName(Card\Entity::ID);

        $query->join($this->manager->card->getTableName(), $paymentCardId, '=', $cardId);
    }

    public function getYesterdayVolume()
    {
        $yesterday = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $today = Carbon::today('Asia/Kolkata')->timestamp;

        return $this->getPaymentVolumeBetweenTimestamp($yesterday, $today);
    }

    public function getCurrentMonthVolume()
    {
        $from = Carbon::yesterday('Asia/Kolkata')->startOfMonth()->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp;

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
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp;

        $pid = $this->getAttributeWithTableName(Payment\Entity::MERCHANT_ID);
        $mid = $this->manager->merchant->getAttributeWithTableName(Merchant\Entity::ID);

        return $this->newQuery()
                    ->join($this->manager->merchant->getTableName(), $pid, '=', $mid)
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
                    ->limit(60)
                    ->get();
    }

    public function getMonthTopMerchantVolumeWise()
    {
        $from = Carbon::yesterday('Asia/Kolkata')->startOfMonth()->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp;

        $pid = $this->getAttributeWithTableName(Payment\Entity::MERCHANT_ID);
        $mid = $this->manager->merchant->getAttributeWithTableName(Merchant\Entity::ID);

        return $this->newQuery()
                    ->join($this->manager->merchant->getTableName(), $pid, '=', $mid)
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
                    ->limit(60)
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

    protected function getPaymentVolumeBetweenTimestamp($from, $to)
    {
        $vol = $this->newQuery()
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->selectRaw('SUM(' . Entity::AMOUNT . ') AS amount' . ','.
                       'COUNT(*) AS count')
                    ->first();

        return $vol;
    }

    protected function validateWallet($attribute, $value)
    {
        Processor\Wallet::validateExists($value);
    }
}
