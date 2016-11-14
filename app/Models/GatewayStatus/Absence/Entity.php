<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const ISSUER                        = 'issuer';
    const CARD_TYPE                     = 'card_type';
    const NETWORK                       = 'network';
    const METHOD                        = 'method';
    const FROM                          = 'from';
    const TO                            = 'to';
    const TERMINAL_ID                   = 'terminal_id';
    const REASON_CODE                   = 'reason_code';
    const SOURCE                        = 'source';
    const COMMENT                       = 'comment';
    const PARTIAL                       = 'partial';
    const SCHEDULED                     = 'scheduled';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = [
        self::GATEWAY,
        self::FROM,
        self::TO,
        self::COMMENT,
        self::REASON_CODE,
        self::ISSUER,
        self::SCHEDULED,
        self::PARTIAL,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::TERMINAL_ID,
        self::SOURCE
    ];

    protected $public = [
        self::ID,
        self::GATEWAY,
        self::ISSUER,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
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
        self::PARTIAL   => 'bool'
    ];

    const END_OF_TIME = 2147483647;

    protected $entity = 'gateway_absence';

    protected $generateIdOnCreate = true;

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

    public function getPartial()
    {
        return $this->getAttribute(self::PARTIAL);
    }

    public function getScheduled()
    {
        return $this->getAttribute(self::SCHEDULED);
    }

    public function getFrom()
    {
        return $this->getAttribute(self::FROM);
    }

    public function getTo()
    {
        return $this->getAttribute(self::TO);
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }
}
