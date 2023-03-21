<?php

namespace RZP\Notifications\Dashboard;

use Mail;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\User\Entity as UserEntity;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Notifications\BaseNotificationService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;

class EmailNotificationService extends BaseNotificationService
{

    public function send(): void
    {
        $payload  = $this->getPayload();

        $merchant = $this->args[MerchantConstants::MERCHANT];

        $org      = $this->getOrg($merchant);

        $recipientEmails = $this->getRecipientEmails($merchant);

        if(empty($recipientEmails) === true)
        {
            return;
        }

        try
        {
            $emailInstance = new MerchantDashboardEmail(
                $payload,
                $merchant->toArrayPublic(),
                $merchant->merchantDetail->toArrayPublic(),
                $org->toArray(),
                $recipientEmails,
                $this->event
            );

            Mail::queue($emailInstance);

            $this->trace->info(TraceCode::MERCHANT_NOTIFICATION_VIA_EMAIL_SENT, [
                Events::EVENT => $this->event,
            ]);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::SEND_MERCHANT_EMAIL_NOTIFICATION_FAILED, [
                    Events::EVENT => $this->event,
                    ]
            );
        }

    }

    private function getOrg($merchant)
    {
        $org = $merchant->org ? : $this->app[MerchantConstants::REPO]->org->getRazorpayOrg();

        return $org;
    }

    protected function getPayload()
    {
        return $this->args[MerchantConstants::PARAMS];
    }

    protected function getRecipientEmails(MerchantEntity $merchant)
    {
        $recepientEmails = $merchant->users()
            ->whereIn(UserEntity::ROLE, Events::RECIPIENT_ROLES[$this->event])
            ->pluck(UserEntity::EMAIL)
            ->all();

        return $recepientEmails;
    }
}
