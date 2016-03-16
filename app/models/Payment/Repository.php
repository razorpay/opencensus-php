<?php

namespace Models\Payment;

use EE\Exception;
use Models\Base;
use Models\Merchant\Methods;
use Models\Payment;
use Models\Card;
use Models\Order;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorDescription;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Payment';

    protected $entityFetchParamRules = array(
        Entity::ORDER_ID        => 'sometimes|string|size:20',
    );

    protected $appFetchParamRules = array(
        Entity::STATUS          => 'sometimes|string',
        Entity::VERIFIED        => 'sometimes|in:null,0,1,2',
        Entity::REFUND_STATUS   => 'sometimes|in:null,partial,full',
        Entity::BANK            => 'sometimes',
        Entity::METHOD          => 'sometimes',
        Entity::GATEWAY         => 'sometimes',
        Entity::EMAIL           => 'sometimes',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::CARD_ID         => 'sometimes|alpha_num|size:14',
        Entity::CAPTURED        => 'sometimes|in:0,1',
        Entity::WALLET          => 'sometimes|',
        Card\Entity::IIN        => 'sometimes|integer|digits:6',
        Card\Entity::LAST4      => 'sometimes|string|digits:4',
    );

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->status(Payment\Status::CAPTURED)
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
        $repo = $this->repo;
        return $repo::whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where(Entity::STATUS, '=', Status::CAPTURED)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->get();
    }

    public function countPaymentsForPricingRuleId($pricingRuleId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PRICING_RULE_ID, '=', $pricingRuleId)
                    ->count();
    }

    public function lockForUpdate($id)
    {
        $repo = $this->repo;

        $repo::lockForUpdate()->findOrFail($id);
    }

    public function timeoutOldPayments($timestamp)
    {
        $repo = $this->repo;

        return $repo::status(Payment\Status::CREATED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->update(
                        array(
                            Payment\Entity::STATUS => Payment\Status::FAILED,
                            Payment\Entity::ERROR_CODE => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                            Payment\Entity::ERROR_DESCRIPTION => PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT)
                        );
    }

    public function getAuthorizedPaymentsBeforeTimestamp($timestamp)
    {
        $repo = $this->repo;

        return $repo::status(Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->orderBy(Payment\Entity::MERCHANT_ID)
                    ->get();
    }

    public function getAuthorizedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        $repo = $this->repo;

        return $repo::status(Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CREATED_AT, '>', $timeLowerLimit)
                    ->get();
    }

    public function getAutoCapturedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        $repo = $this->repo;

        return $repo::status(Payment\Status::CAPTURED)
                    ->where(Payment\Entity::AUTO_CAPTURED, '=', true)
                    ->where(Payment\Entity::CAPTURED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CAPTURED_AT, '>', $timeLowerLimit)
                    ->orderBy(Payment\Entity::MERCHANT_ID, 'desc')
                    ->orderBy(Payment\Entity::ID, 'desc')
                    ->get();
    }

    public function get50PaymentsWithVerifyResult($result)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::VERIFIED, '=', $result)
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
        $repo = $this->repo;

        $verifyEnabledGateways = Payment\Gateway::$verifyEnabled;

        return $repo::whereNull(Payment\Entity::VERIFIED)
                    ->status(Payment\Status::FAILED)
                    ->whereIn(Payment\Entity::GATEWAY, $verifyEnabledGateways)
                    ->createdAtLessThan($ts)
                    ->take(50)
                    ->get();
    }

    public function getNonTaxComputedPayments()
    {
        $repo = $this->repo;

        return $repo::whereNotNull(Payment\Entity::CAPTURED_AT)
                    ->whereNull(Payment\Entity::SERVICE_TAX)
                    ->take(500)
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

    protected function addQueryCaptured($query, $params)
    {
        $captured = $params[Entity::CAPTURED];

        if ($captured === '0')
        {
            $query->whereNull(Entity::CAPTURED_AT);
        }
        else
        {
            $quere->whereNotNull(Entity::CAPTURED_AT);
        }
    }

    protected function addQueryParamOrderId($query, $params)
    {
        $order_id = (new Order\Entity)->verifyIdAndStripSign($params[Entity::ORDER_ID]);

        $query->where(Entity::ORDER_ID, '=', $order_id);
    }

    protected function joinQueryCard($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ? $joins : [];

        $joined = false;

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
}
