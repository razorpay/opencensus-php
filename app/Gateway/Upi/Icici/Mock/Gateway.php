<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Icici;

class Gateway extends Icici\Gateway
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

    /**
     * This is the privateKey for the Gateway Client
     */
    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.key');
    }

    protected function getUrl($type = 'authorize')
    {
        $url = parent::getUrl($type);

        $url = Route::getUrlWithPublicAuth('mock_upi_icici_payment',
                                            ['bank' => 'icici']);
        return $url;
    }
}
