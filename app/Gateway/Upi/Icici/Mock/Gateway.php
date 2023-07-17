<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Icici;
use RZP\Gateway\Upi\Base\Mock as UpiMock;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;
    use UpiMock\GatewayTrait;

    /**
     * We use a tiny 128 bit key for mock
     * testing which is committed as well
     */
    protected function getPublicKey(): string
    {
        return file_get_contents(__DIR__ . '/keys/mockserver.pub');
    }

    /**
     * This is the privateKey for the Gateway Client
     *
     * @param bool $isUpiTransfer
     *
     * @return string
     */
    protected function getPrivateKey(bool $isUpiTransfer): string
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.key');
    }

    public function getQrRefId($input): string
    {

        return 'icicirefID';
    }
}
