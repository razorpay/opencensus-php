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

    public function findByStatusBetweenTimestamps($status, $from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Payment\Entity::STATUS, '=', $status)
                    ->where(Common::CREATED_AT, '>=', $from)
                    ->where(Common::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->where(Payment\Entity::STATUS, '=', Payment\Status::CAPTURED)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
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
}