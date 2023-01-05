<?php

namespace RZP\Models\Payment\Processor;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\QrPaymentRequest;
use RZP\Models\Reward\RewardCoupon\Core as RewardCouponCore;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\Utility;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Base\OrgWiseConfig;
use RZP\Mail\Payment as PaymentMail;
use RZP\Models\Invoice\ViewDataSerializer;
use RZP\Models\Merchant\Email as MerchantEmail;
use RZP\Models\Reward\Repository as RewardRepository;
use RZP\Models\Offer\EntityOffer\Repository as EntityOfferRepository;

class Notify
{
    /**
     * The minimum amount for a transaction to be considered risky
     * This is used to decide low and high value transactions and pick
     * the correct slack channel. Currently set to INR 10000
     */
    const MIN_RISK_AMOUNT = 1000000;

    /**
     * This is the minimum risk rating for a merchant that prompts a
     * post on the RISKY channel. The scale goes from 1-5. This is
     * decided by our risk team.
     */
    const MIN_HIGH_RISK_RATING = 3;
    const HIGH_RISK_RATING     = 4;
    const MAX_HIGH_RISK_RATING = 5;

    /**
     * @var Payment\Entity
     */
    protected $payment;
    /**
     * @var Payment\Refund\Entity
     */
    protected $refund;
    protected $merchant;
    protected $org;
    protected $mode;
    protected $trace;
    protected $template;
    protected $invoice = null;
    protected $slackEnabled = true;
    protected $elfin;
    protected $fetchReward = false;

    /**
     * @var \RZP\Services\Raven
     */
    protected $raven;

    /**
     * Creates a new Notify instance
     *
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Payment\Entity $payment, $fetchReward = false)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;

        $this->fetchReward = $fetchReward;

        $this->merchant = $this->payment->merchant;

        $this->org = $this->merchant->org;

        if ($this->payment->hasInvoice())
        {
            $this->invoice = $this->payment->invoice;
        }

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->refreshTemplate();
    }

    /**
     * Regenerates the entire template
     */
    protected function refreshTemplate()
    {
        $this->template = $this->templateData();
    }

    /**
     * Allows the notifier to be used for a refund as well
     *
     * @param Payment\Refund\Entity $refund Refund entity
     */
    public function addRefund(Payment\Refund\Entity $refund)
    {
        $this->refund = $refund;
        $this->refreshTemplate();
    }

    /**
     * Returns email data required for refund emails
     *
     * @param Payment\Refund\Entity $refund Refund entity
     */
    public function getEmailDataForRefund(Payment\Refund\Entity $refund)
    {
        $this->refund = $refund;

        return $this->templateData();
    }

    /**
     * Sends out mails for a particular event trigger
     *
     * @param string $event
     */
    protected function notifyViaMail(string $event)
    {
        if (Payment\Event::isCustomerEvent($event) === true)
        {
            $mailableClass = $this->getMailableClass($event);

            // For customer, in case of captured event we are sending mail of authorized template
            if ($event === Payment\Event::CAPTURED)
            {
                $mailableClass = $this->getMailableClass(Payment\Event::AUTHORIZED);
            }

            $mailable = new $mailableClass($this->template);

            if ($this->invoice !== null)
            {
                if (in_array($event, Payment\Event::INVOICE_EVENTS, true) === true)
                {
                    $invoiceData = (new ViewDataSerializer($this->invoice))->serializeForInternal();
                    $mailable->setInvoiceDetails($invoiceData);
                }
            }

            if ($this->isCustomerMailEnabled($mailable, $event) === true)
            {
                Mail::queue($mailable);
            }
        }

        if (Payment\Event::isMerchantEvent($event) === true)
        {
            $mailableClass = $this->getMailableClass($event);

            $mailable = new $mailableClass($this->template, true);

            if ($this->invoice !== null)
            {
                if (in_array($event, Payment\Event::INVOICE_EVENTS, true) === true)
                {
                    $invoiceData = (new ViewDataSerializer($this->invoice))->serializeForInternal();
                    $mailable->setInvoiceDetails($invoiceData);
                }
            }

            if ($this->isMerchantMailEnabled($mailable) === true)
            {
                Mail::queue($mailable);
            }
        }
    }

    protected function getRavenSendRewardRequestInput( ): array
    {

        $this->trace->info(
            TraceCode::REWARD_GETTING_RAVEN_REQUEST);

        $template = "sms.m2m_reward_v3";

        $this->elfin = $this->app['elfin'];

        $shortenedUrl = $this->elfin->shorten("https://api.razorpay.com/v1/rewards/redirect/".$this->template['rewards'][0]['id']."/".$this->template['payment']['id']);

        $offer_name = $this->template['rewards'][0]['name'];

//        Splitting reward name into multiple strings as in raven one dynamic var can have max 30 chars

        $offer_name_1 = "";

        $offer_name_2 = "";

        if(strlen($offer_name) > 25)
        {
            $offer_name_1 = substr($offer_name, 0, 25);

            $offer_name_2 = substr($offer_name, 25,strlen($offer_name)-25 );
        }
        else
        {
            $offer_name_1 = $offer_name;
        }

        $payload = [
            'receiver' => $this->template['customer']['phone'],

            'source'   => "api.reward.".$this->template['rewards'][0]['coupon_code'],

            'template' => $template,

            'params'   => [
                'offer_name_1' => $offer_name_1,
                'offer_name_2' => $offer_name_2,
                'coupon' => $this->template['rewards'][0]['coupon_code'],
                'url' =>  $shortenedUrl,
                'merchant_name' =>  $this->template['merchant']['billing_label'],

            ],
        ];

        if (empty($this->template['org']['id']) === false)
        {
            $payload['stork']['context']['org_id'] = $this->template['org']['id'];
        }

        return $payload;
    }

    public function notifyViaSms()
    {

        try
        {
            $this->raven = $this->app['raven'];

            $request = $this->getRavenSendRewardRequestInput();

            $this->trace->info(
                TraceCode::REWARD_SMS_GET_REQUEST_INPUT);

            $response = $this->raven->sendSms($request, false);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, TraceCode::REWARD_SMS_NOTIFICATION_FAILED);
        }

        if (isset($response['sms_id']) === true)
        {
            try
            {
                $properties = [];

                $properties['payment_id'] = $this->template['payment']['id'];

                $properties['reward_id'] = $this->template['rewards'][0]['id'];

                $properties['coupon_code'] = $this->template['rewards'][0]['coupon_code'];

                $properties['publisher merchant_id'] = $this->template['merchant']['id'];

                $properties['contact_number'] =  $this->template['customer']['phone'];

                $this->app['diag']->trackRewardEvent(EventCode::REWARD_SMS_SENT, null, null, $properties);

            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);
            }

        }
    }

    public function triggerSms()
    {
            try
            {
                if(empty($this->template['rewards']) === false)
                {
                    $this->trace->info(
                        TraceCode::REWARD_SMS_FLOW_TRIGGERED,[
                            "rewardId" => $this->template['rewards'][0]['id']
                    ]);
                    $this->notifyViaSms();
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::REWARD_SMS_NOTIFICATION_FAILED
                );
            }
    }

    /**
     * @Deprecated
     */
    protected function notifyViaSlack($event)
    {
        $slackMessages = [
            Payment\Event::FAILED_TO_AUTHORIZED       => 'Failed Payment Authorized',
            Payment\Event::AUTHORIZED                 => 'Payment Authorized',
            Payment\Event::INVOICE_PAYMENT_AUTHORIZED => 'Payment Authorized',
            Payment\Event::REFUNDED                   => 'Payment Refunded'
        ];

        $settings = [
            'channel' => $this->getSlackChannel(),
            'color'   => $this->getSlackPostColor(),
        ];

        // Send out Slack notifications for the event
        // You can control slack posts via SLACK_ENABLE
        if ((array_key_exists($event, $slackMessages)) and
            ($this->isSlackEnabled()))
        {
            $slackData = $this->getSlackData($event);

            $this->app['slack']->queue($slackMessages[$event], $slackData, $settings);
        }
    }

    /**
     * Returns color to use for slack posts
     *
     * @return string
     */
    protected function getSlackPostColor()
    {
        switch ($this->template['payment']['risk'])
        {
            case 1:
            case 2:
            case 3:
                return 'good';
                break;
            case 4:
                return 'warning';
            case 5:
                return 'danger';
            // Peter River color from flatuicolors.com
            default:
                return '#4AA3DF';
        }
    }

    /**
     * Returns the slack channel to be used for posting
     */
    protected function getSlackChannel()
    {
        $config = $this->app['config'];

        $riskRating = $this->template['payment']['risk'];

        $amount = $this->template['payment']['raw_amount'];

        // The priority order is important here
        if ($riskRating === self::MAX_HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.highrisk');
        }
        else if ($riskRating === self::HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.high_4');
        }
        else if (($riskRating === self::MIN_HIGH_RISK_RATING) and
                ($amount <= 1000))
        {
            return $config->get('slack.channels.lt_10');
        }
        else if ($riskRating >= self::MIN_HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.risky');
        }
        else if ($this->payment->amount >= self::MIN_RISK_AMOUNT)
        {
            return $config->get('slack.channels.high');
        }

        // We did not find an appropriate channel, so mark
        // slack as disabled
        $this->slackEnabled = false;
    }

    /**
     * This is the primary public method for this class
     *
     * @param  string $event Trigger notifications for this event
     */
    public function trigger(string $event)
    {
        //
        // This is wrapped in a try-catch block as this is not
        // critical path for the payment operation.
        // We should continue running even if this raises critical error.
        //
        try
        {
            $this->notifyViaMail($event);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_NOTIFY_FAILED,
                [
                    'payment_id' => $this->payment->getPublicId(),
                ]);
        }
    }

    protected function getMerchantForSlack()
    {
        $website = $this->template['merchant']['website'];
        $text    = $this->template['merchant']['billing_label'];

        $dashboardLink = $this->merchant->getDashboardEntityLink();
        $merchantId = $this->template['merchant']['id'];

        // If we don't have billing label or website, just send to dashboard
        if (empty($text) or empty($website))
        {
            return "<$dashboardLink|$merchantId>";
        }

        return "<$website|$text> [<$dashboardLink|$merchantId>]";
    }

    /**
     * Returns slack formatted version of a payment id
     *
     * @param  string $id Payment Id
     * @return string
     */
    protected function getPaymentLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/payments/live/$id|pay_$id>";
    }

    /**
     * Returns slack formatted version of a refund id
     *
     * @param  string $id Refund id
     * @return string     Formatted URL to Refund
     */
    protected function getRefundLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/entity/live/refund/$id|rfnd_$id>";
    }

    /**
     * Returns a flat array that is to be sent to Slack for a trigger event
     * We don't need to send out the original payment details for a refund
     * The array keys are flattened (concatenated using dots)
     * Because slack doesn't support nested arrays
     *
     * So payment.amount = INR 500
     *  & payment.currency = INR
     *
     * Would be some common examples
     *
     * @param  string $event Trigger event
     * @return array Flat array of data to be sent to Slack
     */
    protected function getSlackData($event)
    {
        switch ($event)
        {
            // Both cases are the same
            case Payment\Event::FAILED_TO_AUTHORIZED:
            case Payment\Event::AUTHORIZED:
            case Payment\Event::INVOICE_PAYMENT_AUTHORIZED:
                $data = $this->template['payment'];
                $data['id'] = $this->getPaymentLinkForSlack($data['id']);
                unset($data['method'], $data['public_id']);
                break;

            // Capture is unused right now
            case Payment\Event::CAPTURED:
            case Payment\Event::INVOICE_PAYMENT_CAPTURED:
                $data = $this->template['payment'];
                break;

            case Payment\Event::REFUNDED:
                $data = $this->template['refund'];
                $data['id'] = $this->getRefundLinkForSlack($data['id']);
                $data['payment_id'] = $this->getPaymentLinkForSlack($data['payment_id']);
                unset($data['public_id']);
                break;
        }

        // Add merchant data
        $data['merchant'] = $this->getMerchantForSlack();

        if (isset($data['orderId']))
        {
            $orderId = $data['orderId'];
            unset($data['orderId']);
            $data['orderId'] = $orderId;
        }

        // This is for both payments and refund
        if (isset($data['timestamp']))
        {
            unset($data['timestamp']);
        }

        if ((isset($data['risk']) === true) and
            ($data['risk'] === self::MAX_HIGH_RISK_RATING))
        {
            unset($data['risk']);
            $data['email'] = $this->template['customer']['email'];
            $data['phone'] = $this->template['customer']['phone'];
        }

        $data = $this->flatten($data);

        return $data;
    }

    /**
     * Returns template data to be used for mail and slack templates
     *
     * Also includes refund information if provided via addRefund
     *
     * @return array Template data
     */
    protected function templateData()
    {
        $data  = [
            'customer'  => [
                'email' => $this->payment->getEmail(),
                'phone' => $this->payment->getContact()
            ],
            'merchant'  => [
                'billing_label'             => $this->merchant->getBillingLabel(),
                'website'                   => $this->merchant->getWebsite(),
                // This is the reporting email address for the merchant
                'email'                     => $this->merchant->getTransactionReportEmail(),
                'id'                        => $this->merchant->getId(),
                'brand_color'               => $this->merchant->getBrandColorOrDefault(),
                'contrast_color'            => $this->merchant->getContrastOfBrandColor(),
                'brand_logo'                => $this->merchant->getFullLogoUrlWithSize(),
                'name'                      => $this->merchant->getName(),
                'eligible_for_covid_relief' => $this->merchant->isFeatureEnabled(Feature\Constants::COVID_19_RELIEF),
                'report_url'                => 'https://razorpay.com/support/payments/report-merchant/?e=' . base64_encode($this->payment->getPublicId()) . '&s=' . base64_encode('txn_confirm_mail') . '&m=' . base64_encode($this->payment->getEmail()),
            ],
            'payment'   => [
                'id'                   => $this->payment->getId(),
                'public_id'            => $this->payment->getPublicId(),
                'amount'               => $this->payment->getFormattedAmount(),
                'raw_amount'           => $this->payment['base_amount'],
                'adjusted_amount'      => $this->payment->getAdjustedAmountWrtCustFeeBearer(),
                'timestamp'            => $this->payment->getUpdatedAt(),
                'captured_at'          => $this->payment->getAttribute('captured_at'),
                'amount_spread'        => $this->payment->getAmountComponents(),
                // note that payment method is unavailable to the merchant
                'created_at_formatted' => $this->payment->getFormattedCreatedAtWithTimeZone(),
                // note that payment method is unavailable to the merchant
                'method'               => $this->payment->getMethodWithDetail(),
                'orderId'              => $this->payment->getOrderId(),
                'risk'                 => $this->merchant->getRiskRating(),

                'dcc'                  => ($this->payment->isDCC() and $this->merchant->isDCCMarkupVisible()),
                'gateway_amount_spread'=> $this->payment->getAmountComponents($this->payment->isDCC()),
            ],
            'org'       => [
                'id'                   => $this->org->getId(),
                'custom_code'          => $this->org->getCustomCode(),
                "display_name"         => $this->org->getDisplayName(),
                'hostname'             => $this->org->getPrimaryHostName(),
                'logo_url'             => $this->org->getMainLogo(),
            ],
        ];

        // Add Org Data from commit 1dad91cb6e6e here instead of doing in Payment/Base constructor, 
        // that was wrong implementation since child class has power to override not parent
        $orgData = OrgWiseConfig::getOrgDataForEmail($this->merchant);

        $data = array_merge($data, $orgData);

        // add merchant support details
        try
        {
            $supportDetails = (new MerchantEmail\Core)->fetchEmailsByType($this->merchant, MerchantEmail\Type::SUPPORT);

            $data['merchant']['support_details'] = $supportDetails->toArrayPublic();
        }
        catch (\Throwable $e)
        {
            if ($e->getCode() !== ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_DOES_NOT_EXIST)
            {
                $this->trace->traceException($e);
            }
        }

        if ($this->payment->hasCard())
        {
            $card = $this->payment->card;

            $expiryMonth = str_pad($card->getExpiryMonth(), 2, "0", STR_PAD_LEFT);

            $data['card'] = [
                'number'    => '**** **** **** ' . $card->getLast4(),
                'expiry'    => $expiryMonth . '/' . $card->getExpiryYear(),
                'network'   => $card->getNetworkCode(),
                'color'     => $card->getNetworkColorCode(),
            ];
        }

        if ($this->refund)
        {
            $data['refund'] = [
                'id'                   => $this->refund->getId(),
                'amount'               => $this->refund->getFormattedAmount(),
                'amount_components'    => $this->refund->getAmountComponents(),
                'timestamp'            => $this->refund->getCreatedAt(),
                'payment_id'           => $this->refund->payment->getId(),
                'public_id'            => $this->refund->getPublicId(),
                'rrn'                  => $this->refund->getReference1(),
                'created_at_formatted' => Utility::getTimestampFormatted($this->refund->getCreatedAt(), 'jS M, Y'),
            ];
        }

        if ($this->payment->isFailed() === true)
        {
            $data['payment']['error_description'] = $this->payment->getErrorDescription();
        }

        // added dcc components in case it is a dcc transaction
        if ($this->payment->isDCC())
        {
            $paymentMeta = $this->payment->paymentMeta;
            $gatewayAmount = $paymentMeta->getGatewayAmount();
            $gatewayCurrency = $paymentMeta->getGatewayCurrency();

            $fee = $this->payment->getCurrencyConversionFee($this->payment->getAmount(), $paymentMeta->getForexRate(), $paymentMeta->getDccMarkUpPercent());
            $feeAsPerCurrency = $this->payment->getFormattedAmountsAsPerCurrency($gatewayCurrency, $fee);
            $data['payment']['currency_conversion_fee'] = $feeAsPerCurrency;

            $gatewayAmountAsPerCurrency =$this->payment->getFormattedAmountsAsPerCurrency($gatewayCurrency, $gatewayAmount);
            $data['payment']['gateway_amount'] = $gatewayAmountAsPerCurrency;

            $dccBaseAmount = $gatewayAmount - $fee;
            $data['payment']['dcc_base_amount'] = $this->payment->getFormattedAmountsAsPerCurrency($gatewayCurrency, $dccBaseAmount);
        }

        if (($this->payment->isFailed() === false) and $this->fetchReward === true)
        {
            $entityOffers = (new EntityOfferRepository())->findByEntityIdAndType($this->payment->getId(), 'reward');

            if (isset($entityOffers) === true)
            {
                foreach ($entityOffers as $entityOffer)
                {
                    $reward = (new RewardRepository())->find($entityOffer->offer_id);

                    list($couponCode, $couponType) = $reward->getUniqueOrGenericCouponCode();

                    if(isset($couponCode))
                    {
                        if($this->payment->isCustomerMailAbsent() === false)
                        {
                            $data['email_variant'] = $this->getRewardsEmailVariant();
                        } else {
                            $data['email_variant'] = 'none';
                        }

                        $eventProperties = [
                            'reward_id'     => $reward->getId(),
                            'coupon_code'   => $couponCode,
                            'coupon_type'   => $couponType,
                            'brand_name'    => $reward->getBrandName(),
                            'payment_id'    => $this->payment->getId(),
                            'publisher_id'  => $this->merchant->getId(),
                            'email_variant' => $data['email_variant'] ?? ''
                        ];

                        (new RewardCouponCore())->triggerRewardCouponDistributedEvent($eventProperties);

                        $data['rewards'][] = array(
                            'id'            => $reward->getId(),
                            'logo'          => $reward->getLogo(),
                            'ends_at'       => $reward->getEndsAt(),
                            'stats_at'      => $reward->getStartsAt(),
                            'coupon_code'   => $couponCode,
                            'terms'         => $reward->getTerms(),
                            'name'          => $reward->getName(),
                            'display_text'  => $reward->getDisplayText(),
                            'percent_rate'  => $reward->getPercentRate(),
                            'flat_cashback'  => $reward->getFlatCashback(),
                            'max_cashback'  => $reward->getMaxCashback(),
                            'min_amount'    => $reward->getMinAmount(),
                            'merchant_website_redirect_link' => $reward->getMerchantWebsiteRedirectLink(),
                            'brand_name'    => $reward->getBrandName(),
                        );
                    } else {
                        $this->trace->info(TraceCode::NULL_COUPON_CODE, [
                            'payment_id' => $this->payment->getId(),
                            'reward_id'  => $reward->getId()
                        ]);
                    }
                }
            }
        }

        if(
            $this->merchant->isFeatureEnabled(Feature\Constants::SEND_NAME_IN_EMAIL_FOR_QR) &&
            $this->payment->isUpi() === true &&
            $this->payment->getGateway() === Payment\Gateway::UPI_ICICI &&
            $this->payment->isAuthorized() === true &&
            $this->payment->isBharatQr() === true &&
            is_null($this->payment->getReference16()) === false
        )
        {
            $payerName = (new QrPaymentRequest\Core())->getPayerNameBasedOnRefId($this->payment->getReference16());

            if ($payerName !== null)
            {
                $qrCustomer['name'] = $payerName;
                $data['qr_customer'] = $qrCustomer;
            }
        }

        return $data;
    }

    /**
     * Returns whether a key value pair is a timestamp
     * Called after flattening the array
     *
     * @param  string $key   key name
     * @param  mixed  $value value
     * @return boolean
     */
    protected function isTimestamp($key, $value)
    {
        if (substr($key, -9) !== 'timestamp')
        {
            return false;
        }

        return ((is_numeric($value)) and
            ($value <= PHP_INT_MAX) and
            ($value >= -PHP_INT_MAX));
    }


    /**
     * Removes all null and false values from the array
     * Expects a flattened array (no nested arrays)
     * Also converts timestamps to proper datetime
     *
     * @param  array $data data
     * @return array data with all null values removed
     */
    protected function cleanData(array $data)
    {
        foreach ($data as $key => $value)
        {
            // We remove empty values from the array
            // So slack isn't filled with null/false
            if (($value === null) or
                ($value === false))
            {
                unset($data[$key]);
            }

            // Convert timestamps to readable versions
            if ($this->isTimestamp($key, $value))
            {
                $data[$key] = Carbon::createFromTimestamp($value, Timezone::IST)->format('j M Y h:i a');
            }
        }

        return $data;
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     *
     * @param  array  $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    protected function flatten(array $array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result += $this->flatten($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $this->cleanData($result);
    }

    /**
     * Decides if we send a mail to customer for a payment event
     *
     * @param PaymentMail\Base $mailable Mailable object being sent
     *
     * @return bool
     */
    protected function isCustomerMailEnabled(PaymentMail\Base $mailable, $event)
    {
        // If it is a customer mail and the customer's email address
        // is null or void@razorpay.com don't send email
        if ($this->payment->isCustomerMailAbsent() === true)
        {
            return false;
        }

        // If the merchant has disabled customer emails
        // And this was a customer receipt email don't send a mail
        if (($this->merchant->isReceiptEmailsEnabled() === false) and
            ($mailable->isCustomerReceiptEmail() === true))
        {
            return false;
        }

        if (in_array($event, [Payment\Event::AUTHORIZED, Payment\Event::CAPTURED]))
        {
            if (($mailable->isCustomerReceiptEmail() === true) and
                ($event !== $this->merchant->getReceiptEmailTriggerEvent()))
            {
                return false;
            }
        }

        return $this->isEnabled();
    }

    protected function isMerchantMailEnabled(PaymentMail\Base $mailable)
    {
        $merchantTransactionReportEmail = $this->merchant->getTransactionReportEmail();

        // Do not email linked accounts
        if ($this->merchant->isLinkedAccount() === true)
        {
            return false;
        }

        return (($this->isEnabled() === true) and
                (empty($merchantTransactionReportEmail) === false));
    }

    /**
     * Whether to send notifications or not
     * depending on the environment and the mode
     *
     * @return boolean
     */
    protected function isEnabled()
    {
        //
        // We only send notifications if Mode is not TEST
        // or if the env=dev or env=testing
        // so env=dev or env=testing overrides TEST mode
        //
        if ($this->app->environment('dev', 'testing'))
        {
            return true;
        }

        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        return true;
    }

    /**
     * Whether to send slack notifications
     *
     * @return boolean
     */
    protected function isSlackEnabled()
    {
        return (($this->isEnabled() === true) and
                ($this->slackEnabled === true));
    }

    protected function getMailableClass(string $event)
    {
        // Invoice payment mails are in \RZP\Mail\Invoice\Payment namespace
        // hence we return that namespace
        if (Payment\Event::isInvoiceEvent($event) === true)
        {
            $event = Payment\Event::getInvoiceEventName($event);

            return 'RZP\\Mail\\Invoice\\Payment\\' . $event;
        }
        return 'RZP\\Mail\\Payment\\' . studly_case($event);
    }

    protected function getRewardsEmailVariant()
    {
        try
        {
            $properties = [
                'id'            => $this->payment->getId(),
                'experiment_id' => 'HdZP6KSxRaFEyb', //Production splitz experiment id
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = 'control';

            if(isset($response['response']) and isset($response['response']['variant']))
            {
                $variant = $response['response']['variant']['name'];
            }

            return $variant;
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::REWARD_SUBJECT_SPLITZ_REQUEST_ERROR);

            return 'control';
        }

    }
}
