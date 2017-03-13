<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const GATEWAY       = 'gateway';
    const ISSUER        = 'issuer';
    const CARD_TYPE     = 'card_type';
    const NETWORK       = 'network';
    const METHOD        = 'method';
    // todo: candidate for from/to are start/end, begin/end
    const FROM          = 'from';
    const TO            = 'to';
    const TERMINAL_ID   = 'terminal_id';
    const REASON_CODE   = 'reason_code';
    const SOURCE        = 'source';
    const COMMENT       = 'comment';
    const PARTIAL       = 'partial';
    const SCHEDULED     = 'scheduled';
    const PUBLIC        = 'public';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of storing
    // null
    const NA            = 'NA';
    const UNKNOWN       = 'UNKNOWN';
    const ALL           = 'ALL';


    protected $fillable = [
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::COMMENT,
        self::REASON_CODE,
        self::ISSUER,
        self::SCHEDULED,
        self::PARTIAL,
        self::PUBLIC,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::TERMINAL_ID,
        self::SOURCE
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::ISSUER,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::SOURCE,
        self::FROM,
        self::TO,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::PUBLIC,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::GATEWAY,
        self::METHOD,
        self::ISSUER,
        self::NETWORK,
        self::CARD_TYPE,
        self::FROM,
        self::TO,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::FROM      => 'int',
        self::TO        => 'int',
        self::SCHEDULED => 'bool',
        self::PARTIAL   => 'bool',
        self::PUBLIC    => 'bool',
    ];

    protected $defaults = [
        self::ISSUER        => self::UNKNOWN,
        self::TERMINAL_ID   => null,
        self::CARD_TYPE     => self::UNKNOWN,
        self::NETWORK       => self::UNKNOWN,
        self::TO            => null,
        self::COMMENT       => null,
        self::SCHEDULED     => false,
        self::PUBLIC        => true,
        self::PARTIAL       => false,
        self::PUBLIC        => true,
    ];

    protected static $modifiers = [
        Entity::NETWORK,
        Entity::CARD_TYPE,
        Entity::ISSUER,
    ];

    protected static $unsetEditDuplicateInput = [
        Entity::METHOD,
        Entity::GATEWAY,
    ];

    const END_OF_TIME = 2147483647;

    protected $entity = 'gateway_downtime';

    protected $generateIdOnCreate = true;

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }

    // --------------------- Modifiers ------------------------
    protected function modifyNetwork(&$input)
    {
        if (empty($input[Entity::NETWORK]) === true)
        {
            $method = $input[Entity::METHOD] ?? null;

            switch ($method)
            {
                case Payment\Method::NETBANKING:
                case Payment\Method::WALLET:
                    $input[Entity::NETWORK] = Entity::NA;
                    break;

                case Payment\Method::CARD:
                    $input[Entity::NETWORK] = Entity::UNKNOWN;
                    break;
            }
        }
    }

    protected function modifyCardType(&$input)
    {
        if (empty($input[Entity::CARD_TYPE]) === true)
        {
            $method = $input[Entity::METHOD] ?? null;

            switch ($method)
            {
                case Payment\Method::NETBANKING:
                case Payment\Method::WALLET:
                    $input[Entity::CARD_TYPE] = Entity::NA;
                    break;

                case Payment\Method::CARD:
                    $input[Entity::CARD_TYPE] = Entity::UNKNOWN;
                    break;
            }
        }
    }

    protected function modifyIssuer(&$input)
    {
        if (empty($input[Entity::ISSUER]) === true)
        {
            $method = $input[Entity::METHOD] ?? null;

            switch ($method)
            {
                case Payment\Method::NETBANKING:
                    // for all netbankings, the issuer is the gateway itself
                    $input[Entity::ISSUER] = strtolower($input[Entity::GATEWAY]);
                    break;

                case Payment\Method::WALLET:
                    // for all wallets, the issuer is the gateway itself
                    $gateway = strtolower($input[Entity::GATEWAY]);

                    $input[Entity::ISSUER] = Payment\Gateway::getWalletForGateway($gateway);
                    break;

                case Payment\Method::CARD:
                    // we would assume in this case, that we aren't
                    // aware of the affected networks, cards or issuers
                    // we could also assume all. But we are playing
                    // safe here
                    $input[Entity::ISSUER] = Entity::UNKNOWN;
                    break;
            }
        }
    }

    // -------------------- MUTATORS -------------
    protected function setNetworkAttribute($network)
    {
        if ($this->exists === true)
        {
            $oldNetwork = $this->getAttribute(Entity::NETWORK);
            $method = $this->getAttribute(Entity::METHOD);

            if (($method !== Payment\Method::CARD) or
                ($this->isUnknownAllOrNull($oldNetwork) === false))
            {
                return;
            }
        }

        $this->attributes[Entity::NETWORK] = $network;
    }

    protected function setCardTypeAttribute($cardType)
    {
        if ($this->exists === true)
        {
            $oldCardType = $this->getAttribute(Entity::CARD_TYPE);
            $method = $this->getAttribute(Entity::METHOD);

            if (($method !== Payment\Method::CARD) or
                ($this->isUnknownAllOrNull($oldCardType) === false))
            {
                return;
            }
        }

        $this->attributes[Entity::CARD_TYPE] = $cardType;
    }

    protected function setIssuerAttribute($issuer)
    {
        if ($this->exists === true)
        {
            $oldIssuer = $this->getAttribute(Entity::ISSUER);
            $method = $this->getAttribute(Entity::METHOD);

            if (($method !== Payment\Method::CARD) or
                ($this->isUnknownAllOrNull($oldIssuer) === false))
            {
                return;
            }
        }

        $this->attributes[Entity::ISSUER] = $issuer;
    }

    protected function isUnknownAllOrNull($value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::ALL, null], true);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getReasonCode()
    {
        return $this->getAttribute(self::REASON_CODE);
    }

    public function getCardType()
    {
        return $this->getAttribute(self::CARD_TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function isPartial()
    {
        return $this->getAttribute(self::PARTIAL);
    }

    public function isScheduled()
    {
        return $this->getAttribute(self::SCHEDULED);
    }

    public function isPublic()
    {
        return $this->getAttribute(self::PUBLIC);
    }

    public function getFrom()
    {
        return $this->getAttribute(self::FROM);
    }

    public function getTo()
    {
        return $this->getAttribute(self::TO);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getPublic()
    {
        return $this->getAttribute(self::PUBLIC);
    }
}