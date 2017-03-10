<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Exception\LogicException;
use RZP\Models\Payment\Method;

class InputFormatter
{
    public static function format(array $input)
    {
        $method = $input[Entity::METHOD] ?? null;

        switch ($method)
        {
            // for netbanking, the issuer needs to be
            // available. This is part of the validation rule
            // Note: wallets and netbanking have the same
            // applicability as far as network and card_type
            // are concerned
            case Method::NETBANKING:
                if (empty($input[Entity::NETWORK]) === true)
                {
                    $input[Entity::NETWORK] = Entity::NA;
                }

                if (empty($input[Entity::CARD_TYPE]) === true)
                {
                    $input[Entity::CARD_TYPE] = Entity::NA;
                }

                // for all wallets, the issuer is the gateway itself
                if (empty($input[Entity::ISSUER]) === true)
                {
                    $input[Entity::ISSUER] = strtolower($input[Entity::GATEWAY]);
                }

                break;

            case Method::WALLET:

                if (empty($input[Entity::NETWORK]) === true)
                {
                    $input[Entity::NETWORK] = Entity::NA;
                }

                if (empty($input[Entity::CARD_TYPE]) === true)
                {
                    $input[Entity::CARD_TYPE] = Entity::NA;
                }
                // for all wallets, the issuer is the gateway itself
                if (empty($input[Entity::ISSUER]) === true)
                {
                    $gateway = strtolower($input[Entity::GATEWAY]);

                    $input[Entity::ISSUER] = (strpos($gateway, 'wallet_') === 0) ?
                        str_replace("wallet_","",$gateway) : $gateway;
                }

                break;

            case Method::CARD:
                // we would assume in this case, that we aren't
                // aware of the affected networks, cards or issuers
                // we could also assume all. But we are playing
                // safe here
                if (empty($input[Entity::NETWORK]) === true)
                {
                    $input[Entity::NETWORK] = Entity::UNKNOWN;
                }

                if (empty($input[Entity::CARD_TYPE]) === true)
                {
                    $input[Entity::CARD_TYPE] = Entity::UNKNOWN;
                }

                if (empty($input[Entity::ISSUER]) === true)
                {
                    $input[Entity::ISSUER] = Entity::UNKNOWN;
                }

                break;

            default:
                throw new LogicException(
                    'Unknown Method: ' . $input[Entity::METHOD]);
        }

        return $input;
    }

    public static function editFormat(array $input, Entity $downWindow)
    {
        $input[Entity::METHOD] = $downWindow->getMethod();

        return self::format($input);
    }

}