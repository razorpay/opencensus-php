<?php

namespace RZP\Models\Growth;

use Mail;
use RZP\Models\Base;
use RZP\Mail\Growth\PricingBundle;

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
}
