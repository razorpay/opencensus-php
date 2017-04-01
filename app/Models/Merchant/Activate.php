<?php

namespace RZP\Models\Merchant;

use Mail;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Mail\Merchant\Activation as ActivationMail;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Key;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\MailTags;

class Activate extends Base\Core
{
    public function activate($merchant)
    {
        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $plan = $this->repo->merchant->getPricingPlanOrFailPublic($merchant);

        //
        // Ensure that all payment methods enabled for the merchant
        // has an associated pricing assigned
        //
        (new Methods\Core)->checkPricing($merchant);

        // $terminal = (new Terminal\Repository)->getByMerchantId($id);

        // if ($terminal === null)
        // {
        //     throw new Exception\BadRequestException(
        //         ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        // }

        $ba = $this->repo->bank_account->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        (new Merchant\Validator)->validateBeforeActivate($merchant);

        (new Merchant\Core)->createBalance($merchant, 'live');

        $merchant->enableReceiptEmails();

        $merchant->activate();

        $this->repo->saveOrFail($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_ACCOUNT_ACTIVATED,
            ['merchant_id' => $merchant->getId()]);

        $this->app['drip']->sendDripMerchantInfo(Merchant\Action::ACTIVATED, $merchant);

        $this->sendActivationEmail($merchant, $plan);

        return $merchant->toArrayPublic();
    }

    /**
     * Sends activation email to the merchant, cc's notifications
     * Includes pricing details in the email (properly formatted)
     * @param  RZP\Models\Merchant\Entity $merchant merchant entity
     * @return null
     */
    protected function sendActivationEmail($merchant, $plan)
    {
        $activationMail = new ActivationMail($merchant, $plan);

        Mail::send($activationMail);
    }
}
