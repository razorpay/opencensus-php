<?php

namespace RZP\Models\Merchant\Credits;

use App;
use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\Admin\Action;
use RZP\Models\Merchant\Credits;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForKycUsers;
use RZP\Mail\Merchant\RazorpayX\Credits\ConfirmationForChurnedUsers;

class Core extends Base\Core
{
    // This map indicates credit point to money ratio for a product.
    // example for banking, only payouts will be consuming credits and
    // so not adding the concept of a sub product like payouts, FAV etc
    // for payouts currently 1 CP = 1 Rupee.
    protected static $productSubProductCreditPointsToMoneyRatio = [
        Product::BANKING => 1
    ];

    public function getCreditInMoney($credits, $product = Product::BANKING)
    {
        $ratio = self::$productSubProductCreditPointsToMoneyRatio[$product] ?? null;

        if ($ratio === null)
        {
            return $credits;
        }

        return $credits * $ratio;
    }

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

    public function assignCreditsToMerchant(array $input, Merchant\Entity $merchant)
    {

        $credit = $this->createCreditLogWithCreditBalance(
                                            $merchant,
                                            $input);
        return $credit;
    }

    protected function createCreditLogWithCreditBalance($merchant, $input)
    {
        $creditsExpiry = null;

        if (isset($input[Entity::EXPIRED_AT]) === true)
        {
            $creditsExpiry = $input[Entity::EXPIRED_AT];
        }

        $creditBalance = (new Balance\Core)->createOrFetchCreditBalanceOfMerchant(
                                                                $merchant,
                                                                $input[Entity::TYPE],
                                                                $input[Entity::PRODUCT],
                                                                $creditsExpiry);

        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->setAuditAction(Action::CREATE_MERCHANT_CREDITS);

        $creditsLog->merchant()->associate($merchant);

        $creditsLog->balance()->associate($creditBalance);

        $currentMerchantCredits = $creditBalance->getBalance();

        $creditsLog->getValidator()->validateBalanceCredits(
            $creditsLog->getValue(), $currentMerchantCredits, $creditsLog->getType());

        return $this->repo->transaction(function() use ($merchant, $creditsLog, $creditBalance)
        {
            $this->repo->saveOrFail($creditsLog);

            $creditBalance->incrementBalance($creditsLog->getValue());

            return $creditsLog;
        });
    }

    public function checkMerchantStateAndSendCreditEmail($merchant, $credit)
    {
        $product = $credit->getProduct();

        if ($product !== Product::BANKING)
        {
            return;
        }

        $data = [
            Merchant\Constants::MERCHANT => [
                Merchant\Entity::EMAIL  => $merchant->getEmail(),
                Merchant\Entity::NAME   => $merchant->getName(),
            ],
            Entity::CREDITS  => $this->getCreditInMoney($credit->getValue(), $credit->getProduct()),
        ];

        if ($data[Entity::CREDITS] < 0)
        {
            return;
        }
        // if merchant is already KYC verified that means he is already on X
        // but does not uses the X platform. So we will sending them churned
        // user email
        if ($merchant->isActivated() === true)
        {
            $mail = new ConfirmationForChurnedUsers($data);
        }
        else
        {
            // if merchant is not KYC verified that means he is on X
            // but has not submitted his kyc or basically not completed
            // his KYC, so we are sending him email to prompt for it
            $mail = new ConfirmationForKycUsers($data);
        }

        Mail::queue($mail);
    }
}
