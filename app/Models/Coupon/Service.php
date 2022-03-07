<?php

namespace RZP\Models\Coupon;

use App;

use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Common\GenericEmail;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Notifications\Dashboard\Events;

use DateInterval;
use DateTime;
use RZP\Constants\Date;
use RZP\Error\ErrorCode;
use RZP\Services\RazorpayLabs\SlackApp as SlackAppService;
use Throwable;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Coupon;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Store;


class Service extends Base\Service
{
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core()->create($input);

        return $coupon->toArrayAdmin();
    }


    /**
     * @param string $id
     * @param array $input
     * @return mixed
     */
    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::COUPON_UPDATE_REQUEST, $input);

        $coupon = $this->repo->coupon->findOrFailPublic($id);

        $coupon = $this->core()->update($coupon, $input);

        return $coupon->toArrayAdmin();
    }


    public function delete(string $id): array
    {
        $this->trace->info(TraceCode::COUPON_DELETE_REQUEST, ['coupon_id' => $id]);

        $coupon = $this->repo->coupon->findOrFailPublic($id);

        if ($coupon->getUsedCount() > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deleting a used coupon is not allowed');
        }

        $this->repo->deleteOrFail($coupon);

        return $coupon->toArrayDeleted();
    }

    /**
     * Function will validate Coupon and return its respective error message if validation fails.
     * If all validation passes it returns coupon's expire days and credit amount.
     * @param array $input
     * @return array
     * @throws Throwable
     */
    public function validateCouponAndGetDetails(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_VALIDATE_REQUEST, $input);

        $this->trace->count(Merchant\Metric::COUPON_VALIDATE_TOTAL);

        $merchant = app('basicauth')->getMerchant();

        $coupon = $this->validateCouponAndSendFailureEventsIfApplicable($merchant, $input);

        $promotion = $coupon->source;

        //Here expireDays will return null if Schedule doesn't exist. (Credit will exist indefinitely.)
        $expireDays = null;

        if ($promotion->schedule !== null)
        {
            $expireDays = $promotion->schedule->getInterval();
        }

        return [
            'expire_days'   => $expireDays,
            'credit_amount' => $promotion->getCreditAmount(),
        ];
    }

    public function validateCouponAndSendFailureEventsIfApplicable(Merchant\Entity $merchant, array $input)
    {
        try
        {
            (new Validator)->validateInput('apply', $input);

            return $this->core()->validateAndGetDetails($merchant, $input,false);
        }
        catch (Throwable $exception)
        {
            $code = $input[Entity::CODE] ?? '';

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_APPLY_COUPON_CODE_FAILED, $merchant, $exception, [Entity::COUPON_CODE => $code]);

            throw  $exception;
        }
    }

    public function apply(array $input): array
    {
        $this->trace->info(TraceCode::COUPON_APPLY_REQUEST, $input);

        (new Validator)->validateInput('apply', $input);

        $merchant = null;

        if (app('basicauth')->isAdminAuth() === false)
        {
            $merchant = app('basicauth')->getMerchant();
        }
        else
        {
            $merchantId = $input[Entity::MERCHANT_ID] ?? null;

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }

        $result = $this->core()->apply($merchant, $input,false);

        return $result;
    }

    /**
     * @param $input
     * @return mixed
     * @throws Exception\BadRequestException
     */
    public function sendExpiryAlert($input)
    {

        if( !array_key_exists("days",$input))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                null,
                'missing "days" key in payload');
        }
        $dateRanges = [];
        foreach( $input["days"] as $day)
        {
            $date = new DateTime();
            $date -> setTime(0,0);
            $date -> add(DateInterval::createFromDateString($day.' day'));
            $startTimeStamp = $date->getTimestamp();
            $date -> add(DateInterval::createFromDateString('1 day'));
            $endTimeStamp = $date->getTimestamp();
            $range=[$startTimeStamp,$endTimeStamp];
            array_push($dateRanges,$range);
        }
        $response = ['message'=> ""];
        $couponCodes= $this->core()->getExpiringCoupons($dateRanges);
        if(sizeof($couponCodes) === 0)
        {
            $this->trace->info(TraceCode::NO_COUPONS_EXPIRING, $input);
            $response['message'] = 'NO COUPONS EXPIRING';
            return $response;
        }
//        if ($this->app->config->get('slack.is_slack_enabled') === true)
//        {
//            $settings = [];
//            $settings['color'] = $color;
//            $settings['pretext'] = $pretext;
//            $settings['link_names'] = 1;
//            $settings['channel'] =$this->app->config->get('slack.channels.coupon_expiry_alerts');
//
//            $this->app['slack']->queue("test alert for coupon", $payload, $settings);
//        }
        $recipientEmails=[];
        if(array_key_exists("emails",$input))
        {
            $recipientEmails = array_merge($recipientEmails,$input['emails']);
        }

        if(sizeof($recipientEmails) == 0)
        {
            array_push($recipientEmails,'himanshu.gangwar@razorpay.com');
        }
        foreach ($couponCodes as $coupon)
        {
            $payload = [];
            $payload['code'] = $coupon->getCode();
            $endAt=$coupon->getEndAt();
            $dt=new DateTime("@$endAt");
            $payload['end_at'] = $dt->format('Y-m-d H:i:s');
            $this->send($payload,$recipientEmails,$coupon);
        }
        $response['message'] = 'SUCCESSFULLY Generated alerts';
        return $response;
    }

    protected function send($payload,array $recipientEmails, $couponCode): void
    {
        $org  = $this->app[MerchantConstants::REPO]->org->getRazorpayOrg();
        if(empty($recipientEmails) === true)
        {
            return;
        }
        $recipients = ["email"=>$recipientEmails];
        $recipients = array_merge($recipients,["name"=>'user']);

        $sender = ["email"=>'himanshu.gangwar@razorpay.com'];
        $sender = array_merge($sender,["name"=>'coupon_expiry']);

        try
        {
            $template = Events::EMAIL_TEMPLATES[EVENTS::COUPON_EXPIRY_ALERT];
            $subject = 'Alert: Coupon is about to Expire';
            $emailInstance = new GenericEmail(
                $payload,
                $recipients,
                $sender,
                $org->toArray(),
                $template,
                $subject
            );

            Mail::queue($emailInstance);

            $this->trace->info(TraceCode::COUPON_EXPIRY_NOTIFICATION, [
                'COUPONS'=>$couponCode,
            ]);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL
            );
        }

    }


}
