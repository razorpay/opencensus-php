<?php

namespace Models\Payment;

use EE\Exception;
use Models\Base;
use Models\Payment;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorDescription;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Payment';

    protected $appFetchParamRules = array(
        Entity::STATUS          => 'sometimes|in:created,authorized,captured,failed',
        Entity::VERIFIED        => 'sometimes|boolean',
        Entity::REFUND_STATUS   => 'sometimes|in:partial,full',
        Entity::BANK            => 'sometimes',
    );

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->where(Payment\Entity::STATUS, '=', Payment\Status::CAPTURED)
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

    public function lockForUpdate($id)
    {
        $repo = $this->repo;

        $repo::lockForUpdate()->findOrFail($id);
    }

    public function expireAuthorizedPayments($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<', $timestamp)
                    ->update(array(Payment\Entity::STATUS => 'authorization_expired'));
    }

    public function timeoutOldPayments($timestamp)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::CREATED)
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

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->get();
    }

    public function getAuthorizedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CREATED_AT, '>', $timeLowerLimit)
                    ->get();
    }

    public function getAutoCapturedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', Payment\Status::CAPTURED)
                    ->where(Payment\Entity::AUTO_CAPTURED, '=', true)
                    ->where(Payment\Entity::CAPTURED_AT, '<=', $timeUpperLimit)
                    ->where(Payment\Entity::CAPTURED_AT, '>', $timeLowerLimit)
                    ->orderBy(Payment\Entity::MERCHANT_ID, 'desc')
                    ->orderBy(Payment\Entity::ID, 'desc')
                    ->get();
    }

    public function getUnverifiedPayments($ts)
    {
        $repo = $this->repo;

        return $repo::whereNull(Payment\Entity::VERIFIED)
                    ->where(Payment\Entity::STATUS, '=', Payment\Status::FAILED)
                    ->where(Payment\Entity::CREATED_AT, '<', $ts)
                    ->get();
    }

    protected function addQueryParamStatus($query, $params)
    {
        $query = $query->where(Entity::STATUS, '=', $params[Entity::STATUS]);
    }

    protected function addQueryParamVerified($query, $params)
    {
        $query = $query->where(Entity::VERIFIED, '=', $params[Entity::VERIFIED]);
    }

    protected function addQueryParamRefundStatus($query, $params)
    {
        $query = $query->where(Entity::REFUND_STATUS, '=', $params[Entity::REFUND_STATUS]);
    }

    protected function addQueryParamBank($query, $params)
    {
        if (Payment\Processor\Netbanking::isSupportedBank($input['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                Entity::BANK);
        }

        $query = $query->where(Entity::BANK, '=', $params[Entity::BANK]);
    }
}
