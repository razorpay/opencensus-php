<?php

namespace RZP\Gateway\Wallet\Payzapp\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Payzapp;

class Gateway extends Payzapp\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        if ($this->testing)
        {
            $url = $this->route->getUrlWithPublicAuth('mock_wallet_payment', ['wallet' => 'payzapp']);

            $request['content'] .= '***'.$url.'***';
        }

        return $request;
    }

    protected function makePickUpDataRequest($content)
    {
        $returnContent = array(
            'resCode' => '000',
            'resDesc' => 'SUCCESS',
            'data' => [
                'wibmoTxnId'    => $content['wibmoTxnId'],
                'merId'         => $content['merchantInfo']['merId'],
                'merTxnId'      => $content['merTxnId'],
                'merAppData'    => '',
                'pgStatusCode'  => '50020',
                'pgTxnId'       => random_integer(8),
                'cardType'      => 'Visa',
                'txnAmt'        => 50000,
                'cardClassificationType' => 'Credit',
                'cardHash'      => 'cRpzqfJynHah84KRyfGdU4TC5Mg=',
                'cardMasked'    => '4329XXXXXXXX7413',
                'bin'           => '123456',
                'pgAuthCode'    => 'ABCDEF'
            ]
        );

        return $returnContent;
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

    public function refund(array $input)
    {
        parent::refund($input);
    }
}
