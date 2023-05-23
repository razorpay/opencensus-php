<?php

namespace RZP\Models\Growth;

use Mail;
use RZP\Models\Base;
use RZP\Mail\Growth\PricingBundle;
use RZP\Models\Merchant\Credits;

class Service extends Base\Service
{
    public function sendPricingBundleEmail(array $input)
    {
        (new Validator)->validateInput('send_pricing_bundle_email', $input);

        $merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);

        $data = $input['data'];
        $data['merchant'] = $merchant->toArrayPublic();

        switch ($input[Constants::TYPE])
        {
            case Constants::PAYMENT_SUCCESS:
                $mail = new PricingBundle\PaymentSuccess($data);
                break;
            case Constants::PAYMENT_FAILURE:
                $mail = new PricingBundle\PaymentFailure($data);
                break;
            case Constants::WELCOME:
                $mail = new PricingBundle\Welcome($data, $input[Constants::PACKAGE_NAME]);
                break;
        }
        Mail::send($mail);

        return [
            'data' => $data,
        ];
    }

    public function addAmountCredits(array $input)
    {
        (new Validator)->validateInput('add_amount_credits', $input);

        return (new Credits\Service)->grantCreditsForMerchant($input[Constants::MERCHANT_ID], [
            'value' => $input[Constants::AMOUNT],
            'type' => Constants::AMOUNT,
            'campaign' => $input[Constants::CAMPAIGN_NAME],
            'expired_at' => $input[Constants::EXPIRED_AT],
        ]);
    }

}
