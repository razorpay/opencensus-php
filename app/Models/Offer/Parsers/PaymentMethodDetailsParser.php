<?php

namespace RZP\Models\Offer\Parsers;

use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Models\Bank;
use RZP\Models\Processor;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Card;

class PaymentMethodDetailsParser extends BaseParser
{
    protected static $properties = [
        Offer\Entity::ISSUER,
        Offer\Entity::PAYMENT_NETWORK,
        Offer\Entity::PAYMENT_METHOD_TYPE,
        Offer\Entity::PAYMENT_METHOD
    ];

    protected static $localPrefix = 'on';

    public static function getIssuerDescription(Offer\Entity $offer)
    {
        $description = '';

        $issuer = $offer->getIssuer();

        $paymentMethod = $offer->getPaymentMethod();

        if ($issuer === null)
        {
            if ($paymentMethod === Payment\Method::CARD)
            {
                $description = 'all';
            }
        }
        else
        {
            $description = $issuer;
        }

        return $description;
    }

    public static function getPaymentNetworkDescription(Offer\Entity $offer)
    {
        $paymentNetwork = $offer->getPaymentNetwork();

        $paymentMethod = $offer->getPaymentMethod();

        $description = '';

        if ($paymentNetwork === null)
        {
            if ($paymentMethod !== Payment\Method::CARD)
            {
                $description .= 'all';
            }
        }
        else
        {
            switch ($paymentMethod)
            {
                case Payment\Method::WALLET:
                    $description .= Wallet::$fullName[$paymentNetwork];
                    break;

                case Payment\Method::NETBANKING:
                    $description .= Bank\Name::getName($paymentNetwork);
                    break;

                case Payment\Method::CARD:
                    $description .= Card\Network::getFullName($paymentNetwork);
                    break;

                default:
                    break;
            }
        }

        return $description;
    }

    public static function getPaymentMethodTypeDescription(Offer\Entity $offer)
    {
        $paymentMethodType = $offer->getPaymentMethodType();

        $description = '';

        if ($paymentMethodType !== null)
        {
            $description = $paymentMethodType;
        }

        return $description;
    }

    public static function getPaymentMethodDescription(Offer\Entity $offer)
    {
        return $offer->getPaymentMethod();
    }
}
