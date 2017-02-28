<?php

namespace RZP\Gateway\Atom\Mock;

use RZP\Exception;
use RZP\Gateway\Atom;
use RZP\Gateway\Base;
use RZP\Models\Card;

class Gateway extends Atom\Gateway
{
    use Base\Mock\GatewayTrait;

    protected $url = 'http://203.114.240.183/paynetz/epi/fts';

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;
    }

    public function authorize(array $input)
    {
        // Call atom gateway authorize
        $data = parent::authorize($input);

        // The key thing now is to replace redirectUrl from atom's to ours!
        $parts = parse_url($data['url']);

        $baseUrl = $this->route->getUrlWithPublicAuth('mock_atom_choose_org');
        $newRedirectUrl = $baseUrl . '&' . $parts['query'];

        // Put the new redirect url back in!
        $data['url'] = $newRedirectUrl;

        // Voila
        return $data;
    }

    protected function makeMockRequestUrl($request)
    {
        $url = $request['url'];

        if ($request['action'] === 'authorize')
        {
            $mockGatewaysConfig = \Config::get('applications.mock_gateways');
            $secret = $mockGatewaysConfig['secret'];

            $mockUrl = $this->route->getUrl('mock_atom_init_payment', array(), 'rzp_test', $secret);

            $parts = parse_url($url);

            if (isset($parts['query']))
            {
                $mockUrl .= '?' . $parts['query'];
            }

            return $mockUrl;
        }
        else if ($request['action'] === 'verify')
        {
            return $url;
        }
    }
}
