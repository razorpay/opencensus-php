<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const GATEWAY                       = 'gateway';
    const ISSUER                        = 'issuer';
    const CARD_TYPE                     = 'card_type';
    const NETWORK                       = 'network';
    const METHOD                        = 'method';
    const DOWNTIME_FROM                 = 'downtime_from';
    const DOWNTIME_TO                   = 'downtime_to';
    const TERMINAL_ID                   = 'terminal_id';
    const REASON_CODE                   = 'reason_code';
    const SOURCE                        = 'source';
    const COMMENT                       = 'comment';
    const PARTIAL                       = 'partial';
    const SCHEDULED                     = 'scheduled';
    const PUBLIC                        = 'public';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of storing
    // null
    const NA      = 'NA';
    const UNKNOWN = 'UNKNOWN';
    const ALL     = 'ALL';


    protected $fillable = [
        self::GATEWAY,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
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
        self::METHOD,
        self::ISSUER,
        self::NETWORK,
        self::CARD_TYPE,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::DOWNTIME_FROM => 'int',
        self::DOWNTIME_TO   => 'int',
        self::SCHEDULED     => 'bool',
        self::PARTIAL       => 'bool'
    ];

    protected $defaults = [
        self::ISSUER        => self::UNKNOWN,
        self::TERMINAL_ID   => null,
        self::CARD_TYPE     => self::UNKNOWN,
        self::NETWORK       => self::UNKNOWN,
        self::DOWNTIME_TO   => null,
        self::COMMENT       => null,
        self::SCHEDULED     => false,
        self::PUBLIC        => true,
        self::PARTIAL       => false,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::ISSUER,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::SOURCE,
        self::DOWNTIME_FROM,
        self::DOWNTIME_TO,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::PUBLIC,
        self::CREATED_AT,
        self::UPDATED_AT,
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

    public function getDowntimeFrom()
    {
        return $this->getAttribute(self::DOWNTIME_FROM);
    }

    public function getDowntimeTo()
    {
        return $this->getAttribute(self::DOWNTIME_TO);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }
}
