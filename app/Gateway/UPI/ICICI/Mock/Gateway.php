<?php

namespace RZP\Gateway\UPI\ICICI\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\UPI\ICICI;

class Gateway extends ICICI\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    /**
     * We use a tiny 128 bit key for mock
     * testing which is committed as well
     */
    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockserver.pub');
    }

    protected function getUrl($type = null)
    {
        $url = parent::getUrl();

        $url = Route::getUrlWithPublicAuth('mock_upi_icici_payment',
                                            ['bank' => 'icici']);
        return $url;
    }
}
