<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment\Method;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    const ID         = 'id';

    const STATUS     = 'status';
    const SCHEDULED  = 'scheduled';
    const METHOD     = 'method';
    const BEGIN      = 'begin';
    const END        = 'end';
    const SEVERITY   = 'severity';
    const ISSUER     = 'issuer';
    const TYPE       = 'type';
    const NETWORK    = 'network';
    const AUTH_TYPE  = 'auth_type';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const ONGOING    = 'ongoing';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of null
    const NA         = 'NA';
    const UNKNOWN    = 'UNKNOWN';
    const ALL        = 'ALL';

    const INSTRUMENT = 'instrument';
    const BANK       = 'bank';
    const WALLET     = 'wallet';

    protected $fillable = [
        self::BEGIN,
        self::END,
        self::METHOD,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        self::ISSUER,
        self::TYPE,
        self::NETWORK,
        self::AUTH_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::METHOD,
        self::BEGIN,
        self::END,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        self::ISSUER,
        self::TYPE,
        self::NETWORK,
        self::AUTH_TYPE,
        self::INSTRUMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::METHOD,
        self::BEGIN,
        self::END,
        self::STATUS,
        self::SCHEDULED,
        self::SEVERITY,
        // self::ISSUER,
        // self::TYPE,
        // self::NETWORK,
        // self::AUTH_TYPE,
        self::INSTRUMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::BEGIN     => 'int',
        self::END       => 'int',
        self::SCHEDULED => 'bool',
    ];

    protected $dates = [
        self::BEGIN,
        self::END,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::INSTRUMENT,
    ];

    protected $defaults = [
        self::END => null,
    ];

    protected static $sign = 'down';

    protected $entity = EntityConstants::PAYMENT_DOWNTIME;

    protected $generateIdOnCreate = true;

    // ================= Public setters ================

    public function setPublicInstrumentAttribute(array & $array)
    {
         $instrument = [];

        switch ($this->getMethod())
        {
            case Method::CARD:
                $instrument[self::NETWORK] = $this->getNetwork();
                break;

            case Method::NETBANKING:
                $instrument[self::BANK] = $this->getIssuer();
                break;

            case Method::WALLET:
                $instrument[self::WALLET] = $this->getIssuer();
                break;

            default:
                break;
        }

        $array[self::INSTRUMENT] = array_filter($instrument);
    }

    // ================= Setters ================

    public function setEndNow()
    {
        $this->setAttribute(self::END, Carbon::now()->getTimestamp());
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    // ================= Getters ================

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }
}
