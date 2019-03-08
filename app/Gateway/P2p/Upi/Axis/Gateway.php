<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;

use RZP\Gateway\P2p\Upi;
use RZP\Constants\Timezone;
use RZP\Gateway\P2p\Upi\Axis\Request;
use RZP\Gateway\P2p\Upi\Axis\GatewayTrait;

class Gateway extends Upi\Gateway
{
    use GatewayTrait;

    protected $actionMap = [];

    protected $gateway = 'p2p_upi_axis';

    protected function initiateSdkRequest(string $action)
    {
        $request = new Request([
            'id'        => $this->getSdkRequestId(),
            'action'    => $action,
        ]);

        $request->setActionMap($action, $this->actionMap[$action]);

        $request->setGatewayConfig($this->config);

        return $request;
    }

    protected function getTimeStamp()
    {
        return (string) (Carbon::now(Timezone::IST)->getTimestamp() * 1000);
    }

    protected function toBoolean($value)
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $booleanValue;
    }

    protected function getMerchantId()
    {
        return $this->config['merchant_id'];
    }

    protected function getMerchantChannelId()
    {
        return $this->config['merchant_channel_id'];
    }

    protected function getMerchantCategoryCode()
    {
        return $this->config['merchant_category_code'];
    }

    protected function formatMerchantCustomerId($customerId)
    {
        return 'cust.' . $customerId;
    }

    protected function throwP2pGatewayException()
    {
        throw new \Exception('Hi!');
    }
}
