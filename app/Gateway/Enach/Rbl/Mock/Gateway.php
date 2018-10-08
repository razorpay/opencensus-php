<?php

namespace RZP\Gateway\Enach\Rbl\Mock;

use RZP\Constants\Mode;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Gateway\Enach\Rbl;

class Gateway extends Rbl\Gateway
{
    use Base\Mock\GatewayTrait;

    protected $bank = 'rbl';

    public function authorize(array $input)
    {
        if (($input['payment']['method'] === 'emandate') and
            ($input['payment']['auth_type'] === 'netbanking'))
        {
            return $this->authorizeMock($input, 'mock_emandate_payment');
        }

        return $this->authorizeMock($input, 'mock_esigner_payment');
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        if ($route === 'mock_emandate_payment')
        {
            $url = $this->route->getUrl($route, ['bank' => 'rbl']);

            $request['url'] = $url;
        }
        else
        {
            $gateway = Payment\Gateway::ESIGNER_DIGIO;
          
            $gateway = $this->input['authenticate']['gateway'] ?? $gateway;

            $url = $this->route->getUrl($route, ['signer' => $gateway]);

            if ($request['method'] === 'get')
            {
                // The key thing now is to replace the url from gateway to our mock one!
                $parts = parse_url($request['url']);

              if (isset($parts['query']) === true)
              {
                  $url = $url . '?' .$parts['query'];
              }

              $request['url'] = $url;
            }
        }
    }

    protected function setCrypto()
    {
        $this->crypto = new Rbl\Crypto($this->config, $this->mode);

        $cert = file_get_contents(__DIR__ . '/keys/mock_cert.pem');

        $this->crypto->setEncryptionCertificate($cert);
    }
}
