<?php

namespace RZP\Models\Payment;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Constants\Table;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Payment';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = array(
        Entity::ORDER_ID        => 'sometimes|string|size:20',
    );

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL           => 'sometimes',
        Entity::STATUS          => 'sometimes|string',
        Entity::NOTES           => 'sometimes|string|max:500',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::STATUS          => 'sometimes|string',
        Entity::VERIFIED        => 'sometimes|in:null,0,1,2',
        Entity::REFUND_STATUS   => 'sometimes|in:null,partial,full',
        Entity::BANK            => 'sometimes',
        Entity::METHOD          => 'sometimes',
        Entity::GATEWAY         => 'sometimes',
        Entity::EMAIL           => 'sometimes|email',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::CARD_ID         => 'sometimes|alpha_num|size:14',
        Entity::CAPTURED        => 'sometimes|in:0,1',
        Entity::WALLET          => 'sometimes|',
        Entity::NOTES           => 'sometimes|string|max:500',
        Card\Entity::IIN        => 'sometimes|integer|digits:6',
        Card\Entity::LAST4      => 'sometimes|string|digits:4',
        Card\Entity::INTERNATIONAL => 'sometimes|in:0,1',
        Entity::CUSTOMER_ID     => 'sometimes|alpha_num',
        Entity::SAVE            => 'sometimes|in:0,1',
    );

    protected $esWhitelistedParams = [
        Entity::NOTES
    ];

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

    public function lockForUpdate($id)
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

    public function fetchOldCreatedPaymentsForTimeout($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->get();
    }

    public function getAuthorizedPaymentsBeforeTimestamp($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->orderBy(Payment\Entity::MERCHANT_ID)
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

    public function get50PaymentsWithVerifyResult($result)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::VERIFIED, '=', $result)
                    ->take(50)
                    ->get();
    }

    public function getPaymentsWithCreatedStatusForVerification($ts)
    {
        $verifyEnabledGateways = Payment\Gateway::$verifyEnabled;

        return $this->newQuery()
                    ->whereNull(Payment\Entity::VERIFIED)
                    ->status(Payment\Status::CREATED)
                    ->whereIn(Payment\Entity::GATEWAY, $verifyEnabledGateways)
                    ->createdAtLessThan($ts)
                    ->get();
    }

    public function getUnverifiedPayments($ts)
    {
        $verifyEnabledGateways = Payment\Gateway::$verifyEnabled;

        return $this->newQuery()
                    ->whereNull(Payment\Entity::VERIFIED)
                    ->status(Payment\Status::FAILED)
                    ->whereIn(Payment\Entity::GATEWAY, $verifyEnabledGateways)
                    ->createdAtLessThan($ts)
                    ->take(50)
                    ->get();
    }

    public function fetchPaymentsForCustomerMethod($customer, $method, $skip)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::METHOD, '=', $method)
                    ->where(Payment\Entity::GLOBAL_CUSTOMER_ID, '=', $customer->getId())
                    ->whereNotNull(Payment\Entity::CAPTURED_AT)
                    ->skip($skip)
                    ->take(10)
                    ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, ['card']);
    }

    public function fetchReconciledPaymentsForGateway($from, $to, $gateway, $status)
    {
        $paymentAttrs = Entity::getAttributeWithTableName('*');

        $paymentId = Entity::getAttributeWithTableName(Entity::ID);

        $transactionPaymentId = Transaction\Entity::getAttributeWithTableName(Transaction\Entity::ENTITY_ID);

        $transactionEntityType = Transaction\Entity::getAttributeWithTableName(Transaction\Entity::TYPE);

        $transactionReconciledAt = Transaction\Entity::getAttributeWithTableName(Transaction\Entity::RECONCILED_AT);

        return $this->newQuery()
                    ->select($paymentAttrs)
                    ->join(Table::TRANSACTION, $paymentId, '=', $transactionPaymentId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where($transactionEntityType, '=', 'payment')
                    ->whereBetween($transactionReconciledAt, [$from, $to])
                    ->whereIn(Entity::STATUS, $status)
                    ->get();
    }

    public function fetchBilldeskRefunds($ts)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::GATEWAY, '=', Payment\Gateway::BILLDESK)
                    ->where(Payment\Entity::STATUS, '=', Payment\Status::REFUNDED)
                    ->where(Payment\Entity::CREATED_AT, '>', $ts)
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
        $international = Payment\Entity::getAttributeWithTableName(Entity::INTERNATIONAL);

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

    protected function addQueryParamOrderId($query, $params)
    {
        $orderId = (new Order\Entity)->verifyIdAndSilentlyStripSign($params[Entity::ORDER_ID]);

        $query->where(Entity::ORDER_ID, '=', $orderId);
    }

    protected function joinQueryCard($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ? $joins : [];

        foreach ($joins as $join)
        {
            if ($join->table === Card\Entity::getTableName())
            {
                return;
            }
        }

        $paymentCardId = Payment\Entity::getAttributeWithTableName(Payment\Entity::CARD_ID);
        $cardId = Card\Entity::getAttributeWithTableName(Card\Entity::ID);

        $query->join(Card\Entity::getTableName(), $paymentCardId, '=', $cardId);
    }

    public function getYesterdayVolume()
    {
        $yesterday = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $today = Carbon::today('Asia/Kolkata')->timestamp;

        return $this->getPaymentVolumeBetweenTimestamp($yesterday, $today);
    }

    public function getCurrentMonthVolume()
    {
        $from = Carbon::today('Asia/Kolkata')->startOfMonth()->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp;

        return $this->getPaymentVolumeBetweenTimestamp($from, $to);
    }

    public function getCreatedPaymentsForOrder($orderId)
    {
        $ts = time() - Analytics\Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->whereIn(Entity::STATUS, [Status::CREATED, Status::FAILED])
                    ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                    ->where(Payment\Entity::CREATED_AT, '>', $ts)
                    ->get();
    }

    public function getYesterdayTopMerchantVolumeWise()
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp;

        $pid = Payment\Entity::getAttributeWithTableName(Payment\Entity::MERCHANT_ID);
        $mid = Merchant\Entity::getAttributeWithTableName(Merchant\Entity::ID);

        return $this->newQuery()
                    ->join(Merchant\Entity::getTableName(), $pid, '=', $mid)
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
                    ->limit(30)
                    ->get();
    }

    protected function getPaymentVolumeBetweenTimestamp($from, $to)
    {
        $vol = $this->newQuery()
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->sum(Entity::AMOUNT);

        return $vol;
    }
}
