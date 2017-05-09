<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\FirstData;

class Gateway extends FirstData\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $subscriptionId = $input['payment']['subscription_id'];

        $subscription = $this->app['repo']->subscription->findOrFail($subscriptionId);

        $notes = $subscription->getNotes()->toArray();

        if (($this->env === 'testing') and
            (isset($notes['fail']) === true) and
            ($notes['fail'] === 'capture'))
        {
            throw new Exception\LogicException(
                'Generic failure message');
        }
    }
}
