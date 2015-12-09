<?php

namespace Gateway\Wallet\Payzapp\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Wallet\Payzapp;

class Gateway extends Payzapp\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->testing)
        {
            $url = \Http\Route::getUrlWithPublicAuth('mock_wallet_payment', ['wallet' => 'payzapp']);

            $request['content'] .= '***'.$url.'***';
        }

        return $request;
    }

    protected function getWIapDefaults($input)
    {
        $wIapDefaults = array(
            // WebSDK Configurations
            'wIapManualTrigger'         => true,
            'wIapButtonId'              => 'wIapBtn',
            'wIapWibmoDomain'           => $this->getUrlDomain(),
            'wIapInlineResponse'        => false,
            'wIapInlineResponseHandler' => 'handleWibmoIapResponse',
            'wIapReturnUrl'             => $input['callbackUrl'],
        );

        return $wIapDefaults;
    }

    protected function getAuthContent($input)
    {
        $content = parent::getAuthContent($input);

        $content['wIapDefaults'] = $this->getWIapDefaults($input);

        return $content;
    }
}
