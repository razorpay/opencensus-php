<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Merchant;

/**
* Defines logic for formatting and displaying relevant data for a downtime via
* any public facing api such as checkout preferences
*/
class ViewDataSerializer extends Core
{
    protected $downtimes;

    protected $merchant;

    public function __construct(Base\PublicCollection $downtimes, Merchant\Entity $merchant)
    {
        $this->downtimes = $downtimes;

        $this->merchant = $merchant;
    }

    public function format()
    {
        $formattedData = [];

        $this->downtimes->each(function ($downtime) use ($formattedData)
        {
            $method = $downtime->getMethod();

            switch ($method)
            {
                case Method::CARD:
                    $downtimeData = $this->getFormattedDowntimeDataForCard($downtime);
                    break;

                default:
                    # code...
                    break;
            }
        });
    }

    protected function getFormattedCheckoutDataForCard(Entity $downtime)
    {
        $data = $downtime->toArrayCheckout();

        $gateway = $downtime->getGateway();

        $network = $downtime->getNetwork();

        $issuer = $downtime->getIssuer();

        // If network or issuer is unknown / not available we don't display the data
        if (($this->isUnknownOrNA($network) === true) or ($this->isUnknownOrNA($network) === true))
        {
            return null;
        }

        //
        // If all gateways are affected for the given downtime params then we display the
        // data
        //
        if ($gateway === Entity::ALL)
        {
            return $data;
        }

        if ($network === Entity::ALL)
        {
            $exclusiveNetworks = Payment\Gateway::getExclusiveNetworksForGateway($gateway);
        }
    }

    protected function isUnknownOrNA(string $value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::NA], true);
    }
}
