<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Netbanking;

/**
* Defines logic for formatting and displaying relevant data for a downtime via
* any public facing api such as checkout preferences
*/
class DataFormatter extends Core
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

        foreach ($this->downtimes as $downtime)
        {
            $method = $downtime->getMethod();

            $terminalId = $downtime->getTerminalId();

            $downtimeData = null;

            // If downtime has a terminal, then only show it for the particular merchant and no one else
            if ($downtime->hasTerminal() === true)
            {
                $terminal = $downtime->terminal;

                if ($terminal->getMerchantId() !== $this->merchant->getId())
                {
                    continue;
                }
            }

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

                    $downtimeData = $downtime->getDataForView();

                    $issuer = $downtime->getIssuer();

                    // For wallet and UPI if issuer is unknown ot NA don't display the data
                    if ($this->isUnknownOrNA($issuer) === true)
                    {
                        $downtimeData = null;
                    }

                    break;

                default:
                    break;
            }

            if ($downtimeData !== null)
            {
                $formattedData[$method][] = $downtimeData;
            }
        }

        return $formattedData;
    }

    protected function getFormattedDowntimeDataForCard(Entity $downtime)
    {
        $data = $downtime->getDataForView();

        $gateway = $downtime->getGateway();

        $network = $downtime->getNetwork();

        $issuer = $downtime->getIssuer();

        // If network or issuer is unknown / not available we don't display the data
        if (($this->isUnknownOrNA($network) === true) or ($this->isUnknownOrNA($issuer) === true))
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
        // don't show it, as we can always retry via another card
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
        $data = $downtime->getDataForView();

        $gateway = $downtime->getGateway();

        $issuer = $downtime->getIssuer();

        // Forn netbanking if gateway if ALL, we display the data
        if ($gateway === Entity::ALL)
        {
            return $data;
        }

        // If issuer is unknown or NA, we don't display the data
        if ($this->isUnknownOrNA($issuer) === true)
        {
            return null;
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

        // For directly supporteed gateways we always dsiplay the data
        if (Payment\Gateway::isDirectNetbankingGateway($gateway) === true)
        {
            $data[Entity::ISSUER] = [
                Payment\Gateway::getBankForDirectNetbankingGateway($gateway)
            ];
        }

        return $data;
    }

    protected function isUnknownOrNA(string $value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::NA], true);
    }
}
