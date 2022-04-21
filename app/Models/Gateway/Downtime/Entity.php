<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Models\Payment\Processor\Netbanking;

class Entity extends Base\PublicEntity
{
    use HardDeletes;
    use Base\Traits\Archive;

    protected $archivalEntity = false;

    const ID            = 'id';
    const GATEWAY       = 'gateway';
    const ISSUER        = 'issuer';
    const ACQUIRER      = 'acquirer';
    const CARD_TYPE     = 'card_type';
    const NETWORK       = 'network';
    const METHOD        = 'method';
    const PSP           = 'psp';
    const VPA_HANDLE    = 'vpa_handle';
    const BEGIN         = 'begin';
    const END           = 'end';
    const TERMINAL_ID   = 'terminal_id';
    const REASON_CODE   = 'reason_code';
    const SOURCE        = 'source';
    const COMMENT       = 'comment';
    const PARTIAL       = 'partial';
    const SCHEDULED     = 'scheduled';
    const MERCHANT_ID   = 'merchant_id';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    // the following 3 are for network, issuer and card_type
    // for the appropriate default values instead of storing
    // null
    const NA            = 'NA';
    const UNKNOWN       = 'UNKNOWN';
    const ALL           = 'ALL';

    const SEVERITY      = 'severity';
    const INSTRUMENT    = 'instrument';

    protected $fillable = [
        self::GATEWAY,
        self::BEGIN,
        self::END,
        self::COMMENT,
        self::REASON_CODE,
        self::ISSUER,
        self::ACQUIRER,
        self::SCHEDULED,
        self::PARTIAL,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::PSP,
        self::VPA_HANDLE,
        self::SOURCE,
        self::MERCHANT_ID,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::ISSUER,
        self::ACQUIRER,
        self::CARD_TYPE,
        self::NETWORK,
        self::METHOD,
        self::PSP,
        self::VPA_HANDLE,
        self::SOURCE,
        self::BEGIN,
        self::END,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::GATEWAY,
        self::METHOD,
        self::PSP,
        self::VPA_HANDLE,
        self::ISSUER,
        self::ACQUIRER,
        self::NETWORK,
        self::CARD_TYPE,
        self::BEGIN,
        self::END,
        self::TERMINAL_ID,
        self::REASON_CODE,
        self::COMMENT,
        self::PARTIAL,
        self::SCHEDULED,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::BEGIN     => 'int',
        self::END       => 'int',
        self::SCHEDULED => 'bool',
        self::PARTIAL   => 'bool',
    ];

    protected $defaults = [
        self::GATEWAY       => self::ALL,
        self::ISSUER        => self::UNKNOWN,
        self::ACQUIRER      => self::UNKNOWN,
        self::TERMINAL_ID   => null,
        self::CARD_TYPE     => self::UNKNOWN,
        self::PSP           => null,
        self::VPA_HANDLE    => null,
        self::NETWORK       => self::UNKNOWN,
        self::END           => null,
        self::COMMENT       => null,
        self::SCHEDULED     => false,
        self::PARTIAL       => false,
        self::MERCHANT_ID   => null,
    ];

    protected static $modifiers = [
        Entity::NETWORK,
        Entity::CARD_TYPE,
        Entity::ISSUER,
    ];

    protected static $unsetEditDuplicateInput = [
        Entity::METHOD,
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::SCHEDULED
    ];

    const END_OF_TIME = 2147483647;

    protected $entity = 'gateway_downtime';

    protected $generateIdOnCreate = true;

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity')->withTrashed();
    }

    // -------------------- Overridden ------------------------
    public function edit(array $input = [], $operation = 'edit')
    {
        if ($this->isScheduled() === true)
        {
            return $this;
        }

        return parent::edit($input, $operation);
    }

    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    // --------------------- Modifiers ------------------------
    protected function modifyNetwork(&$input)
    {
        if (empty($input[Entity::NETWORK]) === true)
        {
            $method = $input[Entity::METHOD] ?? null;

            switch ($method)
            {
                case Payment\Method::EMANDATE:
                case Payment\Method::NETBANKING:
                case Payment\Method::WALLET:
                case Payment\Method::UPI:
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
                case Payment\Method::EMANDATE:
                case Payment\Method::NETBANKING:
                case Payment\Method::WALLET:
                case Payment\Method::UPI:
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
        $method = $input[Entity::METHOD] ?? null;

        if (empty($input[Entity::ISSUER]) === true)
        {
            switch ($method)
            {
                case Payment\Method::NETBANKING:

                    $gateway = $input[Entity::GATEWAY];

                    // For shared netbanking gateways if issuer is null then set it to ALL
                    $input[Entity::ISSUER] = Entity::ALL;

                    //
                    // If gateway is a direct netbanking gateway we set the issuer
                    //
                    // @TODO : For direct corporate netbanking integration, currently
                    // it will set the issuer as the retail IFSC, as from the downtime
                    // information, we can't determine whether it was for corporate / retail.
                    // This needs to be fixed.
                    //
                    if (Payment\Gateway::isDirectNetbankingGateway($gateway) === true)
                    {
                        $input[Entity::ISSUER] = Payment\Gateway::getBankForDirectNetbankingGateway($gateway);
                    }

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

                case Payment\Method::UPI:

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

        $this->attributes[Entity::NETWORK] = strtoupper($network);
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

    protected function isUnknownOrNA(string $value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::NA], true);
    }

    public function hasTerminal()
    {
        return $this->isAttributeNotNull(self::TERMINAL_ID);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getAcquirer()
    {
        return $this->getAttribute(self::ACQUIRER);
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

    public function getBegin()
    {
        return $this->getAttribute(self::BEGIN);
    }

    public function getEnd()
    {
        return $this->getAttribute(self::END);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getVpaHandle()
    {
        return $this->getAttribute(self::VPA_HANDLE);
    }

    public function getMerchant()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function setEnd()
    {
        $this->setAttribute(self::END, time());
    }

    public function setEndTime($time)
    {
        $this->setAttribute(self::END, $time);
    }

    /**
     * Formats the downtime entity with relevant data to display on public
     * facing routes like checkout, merchant dashboard etc
     *
     * @return array Formatted downtime data
     */
    public function toArrayCheckout()
    {
        $reasonCode = $this->getReasonCode();

        $data = [
            Entity::ISSUER      => (array) $this->getIssuer(),
            Entity::SEVERITY    => ReasonCode::SEVERITY_MAP[$reasonCode],
            Entity::PARTIAL     => $this->isPartial(),
            Entity::SCHEDULED   => $this->isScheduled(),
            Entity::BEGIN       => $this->getBegin(),
            Entity::END         => $this->getEnd(),
        ];

        if ($this->getMethod() === Payment\Method::CARD)
        {
            $data[Entity::CARD_TYPE] = $this->getCardType();

            $data[Entity::NETWORK] = (array) $this->getNetwork();
        }

        return array_filter($data);
    }

    /**
     * Currently this method deals with only netbanking downtimes, as we are only
     * exposing these types of downtimes over public route to merchant
     */
    public function toArrayPublic()
    {
        $reasonCode = $this->getReasonCode();

        $downtimeMetaData = [
            self::METHOD   => $this->getMethod(),
            self::SEVERITY => ReasonCode::getSeverity($reasonCode),
            self::BEGIN    => $this->getBegin(),
            self::END      => $this->getEnd(),
        ];

        $issuer = $this->getIssuer();

        $gateway = $this->getGateway();

        if ($this->isUnknownOrNA($issuer) === true)
        {
            return null;
        }

        return $this->getDetailsForNetbankingDowntime($gateway,
                                                      $issuer,
                                                      $downtimeMetaData);
    }

    protected function getDetailsForNetbankingDowntime(string $gateway, string $issuer, array $downtimeMetaData)
    {
        $data = [];

        if (in_array($gateway, Payment\Gateway::SHARED_NETBANKING_GATEWAYS_LIVE, true) === true)
        {
            // If issuer is set as ALL, return all issuers exclusive to gateway
            // E.g for billdesk return all banks exclusive to billdesk
            if ($issuer === Entity::ALL)
            {
                $exclusiveIssuers = Netbanking::getExclusiveIssuersForGateway($gateway);

                if (empty($exclusiveIssuers) === true)
                {
                    return null;
                }

                $issuer = $exclusiveIssuers;
            }
            // If particular issuer is present and it is exclusive to the gateway then
            // display the data
            else if (Netbanking::isIssuerExclusiveToGateway($issuer, $gateway) === false)
            {
                return null;
            }
        }
        //
        // For directly supported gateways we always display the data
        // @TODO : We can't differentialte between corporate / retail downtimes.
        // Needs to be fixed
        //
        else if (Payment\Gateway::isDirectNetbankingGateway($gateway) === true)
        {
            $issuer = Payment\Gateway::getBankForDirectNetbankingGateway($gateway);
        }

        if (is_array($issuer) === true)
        {
            foreach ($issuer as $bank)
            {
                $instrumentDetails = $this->getNetbankingInstrumentDetails($bank);

                $data[] = array_filter(array_merge($downtimeMetaData, $instrumentDetails));
            }

        }
        else
        {
            $instrumentDetails = $this->getNetbankingInstrumentDetails($issuer);

            $data = array_filter(array_merge($downtimeMetaData, $instrumentDetails));
        }

        return $data;
    }

    protected function getNetbankingInstrumentDetails(string $issuer): array
    {
        return [
            self::INSTRUMENT => [
                self::ISSUER => $issuer,
            ],
        ];
    }
}
