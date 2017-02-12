<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Card;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Processor;

class PaymentMethodDetailsParser extends BaseParser
{
    protected $properties = [
        Offer\Entity::IINS,
        Offer\Entity::ISSUER,
        Offer\Entity::PAYMENT_NETWORK,
        Offer\Entity::PAYMENT_METHOD_TYPE,
        Offer\Entity::PAYMENT_METHOD
    ];

    protected $localPrefix = 'on';

    protected $localSuffix = '.';

    protected function getIinsDescription()
    {
        $description = '';

        if (empty($this->offer->getIins()) === false)
        {
            $description = 'select';
        }

        return $description;
    }

    protected function getIssuerDescription()
    {
        $description = '';

        if (($this->offer->getPaymentMethod() !== Payment\Method::CARD))
        {
            return $description;
        }

        $issuer = $this->offer->getIssuer();

        if ($issuer === null)
        {
            $description = 'all';

            return $description;
        }

        $description = Netbanking::getName($issuer);

        return $description;
    }

    protected function getPaymentNetworkDescription()
    {
        $paymentNetwork = $this->offer->getPaymentNetwork();

        $paymentMethod = $this->offer->getPaymentMethod();

        $description = '';

        if ($paymentNetwork !== null)
        {
            switch ($paymentMethod)
            {
                case Payment\Method::WALLET:
                    $description .= Wallet::$fullName[$paymentNetwork];
                    break;

                case Payment\Method::NETBANKING:
                    $description .= Netbanking::getName($paymentNetwork);
                    break;

                case Payment\Method::CARD:
                    $description .= Card\Network::getFullName($paymentNetwork);
                    break;

                default:
                    break;
            }

            return $description;
        }

        return $description;
    }

    protected function getPaymentMethodTypeDescription()
    {
        $paymentMethodType = $this->offer->getPaymentMethodType();

        $description = '';

        if ($paymentMethodType !== null)
        {
            $description = $paymentMethodType;
        }

        return $description;
    }

    protected function getPaymentMethodDescription()
    {
        return $this->offer->getPaymentMethod();
    }
}
