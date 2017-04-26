<?php

namespace RZP\Models\Gateway\LoadRule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;

class Entity extends Base\PublicEntity
{
    use Matcher;
    use SoftDeletes;

    const GATEWAY          = 'gateway';
    const MERCHANT_ID      = 'merchant_id';
    const GATEWAY_ACQUIRER = 'gateway_acquirer';
    const INTERNATIONAL    = 'international';
    const METHOD           = 'method';
    const CARD_TYPE        = 'card_type';
    const NETWORK          = 'network';
    const ISSUER           = 'issuer';
    const LOAD             = 'load';
    const DELETED_AT       = 'deleted_at';

    const MAX_LOAD = 10000;

    const LENGTHS = [
        self::GATEWAY   => 50,
        self::NETWORK   => 10,
        self::METHOD    => 30,
        self::CARD_TYPE => 10,
    ];

    const COMPARISON_KEYS = [
        self::GATEWAY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
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
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::ISSUER,
        self::LOAD,
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

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getGatewayAcquirer()
    {
        return $this->getAttribute(self::GATEWAY_ACQUIRER);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function getNormalizedLoad(int $totalLoad)
    {
        $load = ($this->getLoad() / $totalLoad) * self::MAX_LOAD;

        return intval(number_format($load, 2, '.', ''));
    }

    //-----------------Setters----------------------
    public function setLoad(int $load)
    {
        $this->setAttribute[self::LOAD] = $load;
    }
}
