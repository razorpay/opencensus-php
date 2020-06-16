<?php

namespace RZP\Models\Merchant\Credits;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Action;
use RZP\Models\Merchant\Credits;

class Core extends Base\Core
{
    public function create($merchant, $input)
    {
        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->getValidator()->validateCreditsValue($input);

        $creditsLog->setAuditAction(Action::CREATE_MERCHANT_CREDITS);

        $creditsLog->merchant()->associate($merchant);

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        $creditsLog->getValidator()->validateCreditsType($balance, $creditsLog->getType());

        $currentMerchantCredits = $creditsLog->getMerchantCredits();

        $creditsLog->getValidator()->validateBalanceCredits(
            $creditsLog->getValue(), $currentMerchantCredits, $creditsLog->getType());

        $this->app['workflow']->handle((new \stdClass), $creditsLog);

        return $this->repo->transaction(function() use ($merchant, $creditsLog)
        {
            $this->repo->saveOrFail($creditsLog);

            $type = $creditsLog->getType();

            $this->updateCreditsInMerchantAccount($merchant, $creditsLog->getValue(), $type);

            return $creditsLog;
        });
    }

    public function updateCreditsInMerchantAccount($merchant, $credits, $type = Credits\Type::AMOUNT)
    {
        if ($type === Credits\Type::AMOUNT)
        {
            // Add the credits to merchant's main balance
            $merchantAmountCredits = $merchant->primaryBalance->getAmountCredits();

            $newCredits = $merchantAmountCredits + $credits;

            $this->repo->balance->editMerchantAmountCredits($merchant, $newCredits);
        }
        else if ($type === Credits\Type::FEE)
        {
            $merchantFeeCredits = $merchant->primaryBalance->getFeeCredits();

            $newCredits = $merchantFeeCredits + $credits;

            $this->repo->balance->editMerchantFeeCredits($merchant, $newCredits);
        }
        else if ($type === Credits\Type::REFUND)
        {
            $merchantRefundCredits = $merchant->primaryBalance->getRefundCredits();

            $newCredits = $merchantRefundCredits + $credits;

            $this->repo->balance->editMerchantRefundCredits($merchant, $newCredits);
        }
    }

    /*
     * Update credits in the credits Log and merchant credits.
     */
    public function updateCredits($creditsLog, $creditsValue)
    {
        //
        // When we update the credits, We need to subsequently add/subtract credits
        // from merchant balance.
        // Transaction is rolled back if merchant credit balance is less than zero.
        //
        $creditsLog->setAuditAction(Action::EDIT_MERCHANT_CREDITS);

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $creditsLog->merchant->getId() . '_' . $creditsLog->getType();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($creditsLog, $creditsValue)
            {
                $creditsLog->getValidator()->validateNewCreditsValue($creditsLog, (int) $creditsValue);

                return $this->repo->transaction(function() use ($creditsLog, $creditsValue)
                {
                    $creditsDifference = $creditsValue - $creditsLog->getValue();

                    $creditsLog->setValue($creditsValue);

                    $this->repo->saveOrFail($creditsLog);

                    $type = $creditsLog->getType();

                    $this->updateCreditsInMerchantAccount($creditsLog->merchant, $creditsDifference, $type);

                    return $creditsLog;
                });
            },
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            Credits\Constants::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
    }
}
