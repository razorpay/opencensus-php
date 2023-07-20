<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment\Method;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    const ID         = 'id';

    const STATUS      = 'status';
    const SCHEDULED   = 'scheduled';
    const METHOD      = 'method';
    const BEGIN       = 'begin';
    const END         = 'end';
    const SEVERITY    = 'severity';
    const ISSUER      = 'issuer';
    const TYPE        = 'type';
    const NETWORK     = 'network';
    const AUTH_TYPE   = 'auth_type';
    const VPA_HANDLE  = 'vpa_handle';
    const PSP         = 'psp';
    const MERCHANT_ID = 'merchant_id';

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

    const FLOW       = 'flow';

    const INSTRUMENT_SCHEMA = 'instrument_schema';

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
        self::VPA_HANDLE,
        self::PSP,
        self::MERCHANT_ID,
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
        self::VPA_HANDLE,
        self::PSP,
        self::MERCHANT_ID,
        self::AUTH_TYPE,
        self::INSTRUMENT,
        self::INSTRUMENT_SCHEMA,
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
        self::INSTRUMENT_SCHEMA,
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
        self::STATUS,
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
                $network = $this->getNetwork();
                if (isset($network) === true)
                {
                    $instrument[self::NETWORK] = $network;
                }
                $issuer = $this->getIssuer();
                if (isset($issuer) === true)
                {
                    $instrument[self::ISSUER] = $issuer;
                }
                break;

            case Method::NETBANKING:
            case Method::EMANDATE:
            case Method::FPX:
                $instrument[self::BANK] = $this->getIssuer();
                break;

            case Method::WALLET:
                $instrument[self::WALLET] = $this->getIssuer();
                break;

            case Method::UPI:
                $vpaHandle = $this->getVpaHandle();
                $psp = $this->getPSP();
                $issuer = $this->getIssuer();
                if( empty($psp) === false)
                {
                    $instrument[self::PSP] = $psp;

                }
                else if( empty($vpaHandle) === false)
                {
                    $instrument[self::VPA_HANDLE] = $vpaHandle;
                }
                else if( empty($issuer) === false)
                {
                    $instrument[self::ISSUER] = $issuer;
                }
                break;


            default:
                break;
        }

        $this->setInstrumentSchemaAndGranularFields($array, $instrument);

        $array[self::INSTRUMENT] = array_filter($instrument);
    }

    public function setPublicStatusAttribute(array & $array)
    {
        $array[self::STATUS] = $this->getStatusByTime();
    }

    // ================= Setters ================

    private function setInstrumentSchemaAndGranularFields(array & $array, array & $instrument)
    {
        if (($this->getType() !== "NA") && ($this->getType() !== "UNKNOWN"))
        {
            switch ($this->getMethod())
            {
                case Method::CARD:
                    $instrument[self::TYPE] = $this->getType();
                    break;

                case Method::UPI:
                    $instrument[self::FLOW] = $this->getType();
                    break;

                default:
                    break;
            }
        }

        $array[self::INSTRUMENT_SCHEMA] = array_keys($instrument);
    }

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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBegin()
    {
        return $this->getAttribute(self::BEGIN);
    }

    public function getEnd()
    {
        return $this->getAttribute(self::END);
    }

    public function getVpaHandle()
    {
        return $this->getAttribute(self::VPA_HANDLE);
    }

    public function getPSP()
    {
        return $this->getAttribute(self::PSP);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getSeverity()
    {
        return $this->getAttribute(self::SEVERITY);
    }

    public function isScheduled()
    {
        return $this->getAttribute(self::SCHEDULED);
    }

    public function getStatusByTime()
    {
        $now   = Carbon::now(Timezone::IST)->getTimestamp();

        $begin = $this->getBegin();

        $end   = $this->getEnd();

        if (($end === null) or
            ($end > $now))
        {
            return (($begin <= $now) ? Status::STARTED : Status::SCHEDULED);
        }
        else
        {
            return Status::RESOLVED;
        }
    }

    public function toArrayPublic()
    {
        $data = parent::toArrayPublic();

        if (($data[self::STATUS] === Status::STARTED) && (isset($data[self::CREATED_AT]))
            && (isset($data[self::UPDATED_AT])) && ($data[self::CREATED_AT] !== $data[self::UPDATED_AT]))
        {
            $data[self::STATUS] = Status::UPDATED;
        }

        return $data;
    }
}
