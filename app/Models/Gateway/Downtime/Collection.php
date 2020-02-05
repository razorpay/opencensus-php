<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Netbanking;

class Collection extends Base\PublicCollection
{
    /**
     * Formats collection of downtimes for public facing routes with
     * rules to format data accourding to various downtime entity attributes
     *
     * @return array Formatted data
     */
    public function toArrayCheckout()
    {
        $array = [];

        foreach ($this->items as $downtime)
        {
            $method = $downtime->getMethod();

            $downtimeData = null;

            switch ($method)
            {
                case Method::CARD:

                    $downtimeData = $this->getFormattedDowntimeDataForCard($downtime);

                    break;

                case Method::NETBANKING:

                    $downtimeData = $this->getFormattedDowntimeDataForNetbanking($downtime);

                    break;

                case Method::WALLET:
                case Method::UPI:

                    $downtimeData = $downtime->toArrayCheckout();

                    break;

                default:
                    break;
            }

            if ($downtimeData !== null)
            {
                $array[$method][] = $downtimeData;
            }
        }

        return $array;
    }

    public function toArrayPublic()
    {
        $array = [];

        $items = $this->itemsToArrayPublic();

        $array[static::ENTITY] = $this->entity;

        $array[static::COUNT] = count($items);

        $array[static::ITEMS] = $items;

        return $array;
    }

    protected function getFormattedDowntimeDataForCard(Entity $downtime)
    {
        $data = $downtime->toArrayCheckout();

        $gateway = $downtime->getGateway();

        $network = $downtime->getNetwork();

        $issuer = $downtime->getIssuer();

        // If network or issuer is unknown / not available we don't display the data
        if (($this->isUnknownOrNA($network) === true) or
            ($this->isUnknownOrNA($issuer) === true))
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

        //
        // For card downtimes if gateway is not ALL and a specific issuer is given
        // don't show it, as we can always retry via another gateway
        //
        if ($issuer !== Entity::ALL)
        {
            return null;
        }

        //
        // If all networks of a gateway are affected only show networks which are exclusive
        // to the gateway
        //
        if ($network === Entity::ALL)
        {
            $exclusiveNetworks = Payment\Gateway::getExclusiveNetworksForGateway($gateway);

            if (empty($exclusiveNetworks) === true)
            {
                return null;
            }

            $data[Entity::NETWORK] = $exclusiveNetworks;

            return $data;
        }

        if (Payment\Gateway::isNetworkExclusiveToGateway($network, $gateway) === true)
        {
            return $data;
        }
    }

    protected function getFormattedDowntimeDataForNetbanking(Entity $downtime)
    {
        $data = $downtime->toArrayCheckout();

        $gateway = $downtime->getGateway();

        $issuer = $downtime->getIssuer();

        // If issuer is unknown or NA, we don't display the data
        if ($this->isUnknownOrNA($issuer) === true)
        {
            return null;
        }

        // For netbanking if gateway if ALL, we display the data
        if ($gateway === Entity::ALL)
        {
            return $data;
        }

        if (in_array($gateway, Payment\Gateway::SHARED_NETBANKING_GATEWAYS_LIVE, true) === true)
        {
            // If issuer is set as ALL, return all issuers exclusive to gateway
            // E.g for billdesk return all banks exclusive to billdesk
            if ($issuer === Entity::ALL)
            {
                $exclusiveIssuers = Netbanking::getExclusiveIssuersForGateway($gateway);

                if (empty($exclusiveIssuers) === true)
                {
                    return null;
                }

                $data[Entity::ISSUER] = $exclusiveIssuers;

                return $data;
            }

            // If particular issuer is present and it is exclusive to the gateway then
            // display the data
            if (Netbanking::isIssuerExclusiveToGateway($issuer, $gateway) === true)
            {
                return $data;
            }
        }

        //
        // For directly supporteed gateways we always dsiplay the data
        // @TODO : This will show the downtime notification without
        //         distinction between corporate and Non corporate banking options
        //         for certain banks like ICIC. Need to fix this detecting icici netbanking
        //         downtimes separately.
        //
        if (Payment\Gateway::isDirectNetbankingGateway($gateway) === true)
        {
            $data[Entity::ISSUER] = (array) Payment\Gateway::getBankForDirectNetbankingGateway($gateway);

            return $data;
        }
    }

    protected function itemsToArrayPublic(bool $expand = false): array
    {
        $array = [];

        foreach ($this->items as $item)
        {
            $item = $item->toArrayPublic();

            if ($item !== null)
            {
                if (is_associative_array($item) === true)
                {
                    $item = [$item];
                }

                $array = array_merge($array, $item);
            }
        }

        return $array;
    }

    protected function isUnknownOrNA(string $value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::NA], true);
    }
}
