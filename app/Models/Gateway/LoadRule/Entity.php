<?php

namespace RZP\Models\Gateway\LoadRule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const GATEWAY             = 'gateway';
    const MERCHANT_ID         = 'merchant_id';
    const CATEGORY            = 'category';
    const GATEWAY_ACQUIRER    = 'gateway_acquirer';
    const INTERNATIONAL       = 'international';
    const METHOD              = 'method';
    const CARD_TYPE           = 'card_type';
    const NETWORK             = 'network';
    const ISSUER              = 'issuer';
    const LOAD                = 'load';
    const DELETED_AT          = 'deleted_at';

    const ALL      = 'all';
    const MAX_LOAD = 10000;

    const LENGTHS = [
        self::GATEWAY   => 50,
        self::CATEGORY  => 4,
        self::NETWORK   => 10,
        self::METHOD    => 30,
        self::CARD_TYPE => 10,
    ];

    const COMPARISON_KEYS = [
        self::GATEWAY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL
    ];

    protected $entity = 'gateway_load_rule';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::INTERNATIONAL => 'boolean',
        self::LOAD          => 'int',
    ];

    protected $fillable = [
        self::GATEWAY,
        self::MERCHANT_ID,
        self::METHOD,
        self::CARD_TYPE,
        self::NETWORK,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::CATEGORY,
        self::ISSUER,
        self::LOAD,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::MERCHANT_ID,
        self::METHOD,
        self::CARD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::CATEGORY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::LOAD,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $public = [
        self::ID,
        self::GATEWAY,
        self::MERCHANT_ID,
        self::METHOD,
        self::CARD_TYPE,
        self::NETWORK,
        self::CATEGORY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::ISSUER,
        self::LOAD,
    ];

    protected static $modifiers = [
        self::NETWORK,
        self::CARD_TYPE,
        // self::GATEWAY_ACQUIRER,
        self::ISSUER
    ];

    public function getLoad()
    {
        return $this->getAttribute(self::LOAD);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getCardType()
    {
        return $this->getAttribute(self::CARD_TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    // -----------------------Modifiers---------------------
    protected function modifyNetwork(& $input)
    {
        if (empty($input[self::NETWORK]) === true)
        {
            $method = $input[self::METHOD] ?? null;

            switch ($method)
            {
                case Method::CARD:
                case Method::EMI:
                    $input[self::NETWORK] = self::ALL;
                    break;

                default:
                    break;
            }
        }
    }

    protected function modifyCardType(& $input)
    {
        if (empty($input[self::CARD_TYPE]) === true)
        {
            $method = $input[self::METHOD] ?? null;

            if (in_array($method, [Method::CARD, Method::EMI], true) === true)
            {
                $input[self::CARD_TYPE] = self::ALL;
            }
        }
    }

    // protected function modifyGatewayAcquirer(& $input)
    // {
    //     if (empty($input[self::GATEWAY_ACQUIRER]) === true)
    //     {
    //         $method = $input[self::METHOD] ?? null;

    //         if (in_array($method, [Method::CARD, Method::EMI], true) === true)
    //         {
    //             $input[self::GATEWAY_ACQUIRER] = self::ALL;
    //         }
    //     }
    // }

    protected function modifyIssuer(& $input)
    {
        if (empty($input[self::ISSUER]) === true)
        {
            $method = $input[self::METHOD] ?? null;
            $gateway = $input[self::GATEWAY];

            switch ($method)
            {
                case Method::NETBANKING:
                    // For shared netbanking gateways, if issuer is empty set isser to ALL
                    if (in_array($gateway, Gateway::$netbankingGateways, true) === true)
                    {
                        $input[self::ISSUER] = self::ALL;
                    }
                    else
                    {
                        $input[self::ISSUER] = $input[self::GATEWAY];
                    }

                    break;

                case Method::WALLET:
                    // For all wallets, set the issuer to gateway if issuer is empty
                    $input[self::ISSUER] = Gateway::getWalletForGateway($gateway);

                    break;

                case Method::CARD:
                case Method::EMI:
                    // If issuer is empty for card/emi method, we set it to ALL, meaning the load
                    // affects all issuers
                    $input[self::ISSUER] = self::ALL;
                    break;

                default:
                    break;
            }
        }
    }
}
