<?php

namespace RZP\Models\Payout\Notifications;

use App;
use Mail;

use RZP\Constants\Mode;
use RZP\Services\Stork;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\FundAccount;
use RZP\Models\Payout\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Mail\Payout\PayoutProcessedContactCommunication as PayoutProcessedContactCommunicationMailable;

class PayoutProcessedContactCommunication extends Base
{
    protected $payout;

    protected $trace;

    protected $payoutMerchant;

    const SMS_TEMPLATE = 'sms.payout.payout_processed_contact_communication';

   // constants
    const PAYOUT_ID = 'payout_id';

    const PAYOUT_ORIGIN = 'payout_origin';

    const SMS_TEMPLATE_KEY = 'sms_template';

    const CONTEXT = 'context';

    const PAYLOAD = 'payload';

    const EMAIL_ID = 'email_id';

    public function __construct(Entity $payout)
    {
        parent::__construct();

        $this->payout = $payout;

        $this->payoutMerchant = $this->payout->merchant;
    }

    public function notify()
    {
        //If payout source is vendor payment then do not send email
        // TODO: Add a check for dashboard payouts and make the feature default instead of Feature Flag
        // TODO: This is deprecated Feature flag. Will remove post testing the new flow
        if ($this->sendNotificationUsingNewFeatureFlag() == false)
        {
            if ($this->payout->merchant->isFeatureEnabled(
                    Feature\Constants::BENE_EMAIL_NOTIFICATION) === true &&
                $this->payout->isVendorPayment() === false)
            {
                $this->sendEmail();
            }

            if ($this->payout->merchant->isFeatureEnabled(
                    Feature\Constants::BENE_SMS_NOTIFICATION) === true)
            {
                $this->sendSms();
            }

            return;
        }

        /*
         * DISABLE_API_PAYOUT_BENE_EMAIL, DISABLE_DB_PAYOUT_BENE_EMAIL, DISABLE_DB_PAYOUT_BENE_SMS are features to signify blacklist
         * ENABLE_API_PAYOUT_BENE_SMS feature signifies whitelist
         *
         * By default, for payouts created by API and Dashboard, Beneficiary communication will be sent via Email
         * For payouts created by API, Beneficiary communication will be sent via SMS only when ENABLE_API_PAYOUT_BENE_SMS is enabled for the MID
         * However, Beneficiary communication via SMS will be sent when Payouts are created by Dashboard
        */
        $enableApiPayoutBeneEmail = ($this->payout->merchant->isFeatureEnabled(Feature\Constants::DISABLE_API_PAYOUT_BENE_EMAIL) === false);

        if ($this->payout->getOrigin() === Entity::API &&
            $enableApiPayoutBeneEmail === true &&
            $this->payout->isVendorPayment() === false)
        {
            $this->sendEmail();
        }

        $enableDbPayoutBeneEmail = ($this->payout->merchant->isFeatureEnabled(Feature\Constants::DISABLE_DB_PAYOUT_BENE_EMAIL) === false);

        if ($this->payout->getOrigin() === Entity::DASHBOARD &&
            $enableDbPayoutBeneEmail === true &&
            $this->payout->isVendorPayment() === false)
        {
            $this->sendEmail();
        }

        $enableApiPayoutBeneSms = $this->payout->merchant->isFeatureEnabled(Feature\Constants::ENABLE_API_PAYOUT_BENE_SMS);

        if ($this->payout->getOrigin() === Entity::API &&
            $enableApiPayoutBeneSms === true)
        {
            $this->sendSms();
        }

        $enableDbPayoutBeneSms = ($this->payout->merchant->isFeatureEnabled(Feature\Constants::DISABLE_DB_PAYOUT_BENE_SMS) === false);

        if ($this->payout->getOrigin() === Entity::DASHBOARD &&
            $enableDbPayoutBeneSms === true)
        {
            $this->sendSms();
        }

        $this->trace->info(TraceCode::BENE_EMAIL_SMS_NOTIFICATION_FEATURE_FLAG_STATUS,
            [
                'merchant_id'               => $this->payout->merchant->getId(),
                'payout_id'                 => $this->payout->getPublicId(),
                'payout_origin'             => $this->payout->getOrigin(),
                'is_vendor_payout'          => $this->payout->isVendorPayment(),
                'enable_api_payout_email'   => $enableApiPayoutBeneEmail,
                'enable_db_payout_email'    => $enableDbPayoutBeneEmail,
                'enable_api_payout_sms'     => $enableApiPayoutBeneSms,
                'enable_db_payout_sms'      => $enableDbPayoutBeneSms,
            ]);

    }

    private function sendNotificationUsingNewFeatureFlag() :bool
    {
        $sendNotificationUsingNewFeatureFlag = $this->app->razorx->getTreatment($this->payout->merchant->getId(),
            RazorxTreatment::RX_PAYOUT_RECEIPT_BENE_NOTIFICATION,
            MODE::LIVE);

        $this->trace->info(TraceCode::PAYOUT_BENE_NOTIFICATION_EXPERIMENT,
            [
                'merchant_id'        => $this->payout->merchant->getId(),
                'experiment_name'    => RazorxTreatment::RX_PAYOUT_RECEIPT_BENE_NOTIFICATION,
                'experiment_status' => $sendNotificationUsingNewFeatureFlag,
            ]);

        return $sendNotificationUsingNewFeatureFlag === RazorxTreatment::RAZORX_VARIANT_ON;
    }

    protected function getSmsPayload()
    {
        $fundAccount   = $this->payout->fundAccount;
        $contactNumber = $fundAccount->contact->getContact();

        $contactNumber = preg_replace('/^\+/', '', $contactNumber);

        $merchant = $this->payout->merchant;

        $payload = [
            SmsConstants::CONTENT_PARAMS     => [
                'merchant_display_name' => $merchant->getBillingLabel() ?? $merchant->getName(),
                'payout_reference_id'   => $this->payout->getReferenceId() ? 'for '. $this->payout->getReferenceId() :'',
                'payout_utr'            => $this->payout->getUtr(),
                'amount'                => $this->payout->getFormattedAmount()
            ],
            SmsConstants::TEMPLATE_NAME      => self::SMS_TEMPLATE,
            SmsConstants::TEMPLATE_NAMESPACE => SmsConstants::PAYOUTS_CORE_TEMPLATE_NAMESPACE,
            SmsConstants::ORG_ID             => $this->app['basicauth']->getOrgId() ?? '',
            SmsConstants::DESTINATION        => $contactNumber,
            SmsConstants::SENDER             => SmsConstants::RAZORPAYX_SENDER,
            SmsConstants::OWNER_ID           => $this->payoutMerchant->getId(),
            SmsConstants::OWNER_TYPE         => 'merchant',
            SmsConstants::LANGUAGE           => SmsConstants::ENGLISH,
        ];

        return $payload;
    }

    protected function sendSms()
    {
        $payload = $this->getSmsPayload();

        $maskedPayload = $payload;
        $maskedPayload[SmsConstants::DESTINATION] = mask_phone($maskedPayload[SmsConstants::DESTINATION]);

        // add trace for sending sms
        $this->trace->info(TraceCode::PAYOUT_SEND_SMS_INIT,
                           [
                               self::PAYOUT_ID        => $this->payout->getPublicId(),
                               self::PAYOUT_ORIGIN    => $this->payout->getOrigin(),
                               self::SMS_TEMPLATE_KEY => self::SMS_TEMPLATE,
                               self::PAYLOAD          => $maskedPayload
                           ]);

        if (empty($payload[SmsConstants::DESTINATION]) === true)
        {
            return;
        }

        try
        {
            /** @var Stork $stork */
            $stork = $this->app['stork_service'];
            $stork->sendSms($this->mode, $payload, false);

            $this->trace->info(TraceCode::PAYOUT_SEND_SMS_FINISHED,
                               [
                                   self::PAYOUT_ID        => $this->payout->getPublicId(),
                                   self::SMS_TEMPLATE_KEY => self::SMS_TEMPLATE,
                                   self::PAYLOAD          => $maskedPayload
                               ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_SEND_SMS_FAILED,
                [
                    self::PAYOUT_ID        => $this->payout->getId(),
                    self::SMS_TEMPLATE_KEY => self::SMS_TEMPLATE,
                    self::PAYLOAD          => $maskedPayload
                ]);
        }
    }

    protected function sendEmail()
    {
        /** @var FundAccount\Entity $fa */
        $fundAccount  = $this->payout->fundAccount;
        $contactEmail = $fundAccount->contact->getEmail();

        $this->trace->info(TraceCode::PAYOUT_SEND_EMAIL_INIT,
                           [
                               self::PAYOUT_ID => $this->payout->getPublicId(),
                               self::PAYOUT_ORIGIN => $this->payout->getOrigin(),
                               self::CONTEXT   => self::class,
                               self::EMAIL_ID  => mask_email($contactEmail)
                           ]);

        if ($contactEmail !== null)
        {
            $mailable = new PayoutProcessedContactCommunicationMailable($this->payout->getId(), $contactEmail);

            Mail::queue($mailable);

            $this->trace->info(TraceCode::PAYOUT_SEND_EMAIL_FINISHED,
                               [
                                   self::PAYOUT_ID => $this->payout->getId(),
                                   self::CONTEXT   => self::class,
                                   self::EMAIL_ID  => mask_email($contactEmail)
                               ]);
        }
    }
}
