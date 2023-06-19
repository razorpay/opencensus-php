<?php

namespace RZP\Models\CardMandate\MandateHubs;

class Mandate
{
    const MANDATE_ID                 = 'mandate_id';
    const MANDATE_CARD_ID            = 'mandate_card_id';
    const MANDATE_CARD_NAME          = 'mandate_card_name';
    const MANDATE_CARD_LAST4         = 'mandate_card_last4';
    const MANDATE_CARD_NETWORK       = 'mandate_card_network';
    const MANDATE_CARD_TYPE          = 'mandate_card_type';
    const MANDATE_CARD_ISSUER        = 'mandate_card_issuer';
    const MANDATE_CARD_INTERNATIONAL = 'mandate_card_international';
    const MANDATE_SUMMARY_URL        = 'mandate_summary_url';
    const STATUS                     = 'status';
    const DEBIT_TYPE                 = 'debit_type';
    const CURRENCY                   = 'currency';
    const MAX_AMOUNT                 = 'max_amount';
    const AMOUNT                     = 'amount';
    const START_AT                   = 'start_at';
    const END_AT                     = 'end_at';
    const TOTAL_CYCLES               = 'total_cycles';
    const MANDATE_INTERVAL           = 'mandate_interval';
    const FREQUENCY                  = 'frequency';
    const PAUSED_BY                  = 'paused_by';
    const CANCELLED_BY               = 'cancelled_by';


    /**
     * The mandate's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    protected $mandateHub = '';

    function __construct(string $mandateHub, array $attributes)
    {
        $this->mandateHub = $mandateHub;

        foreach ($attributes as $key => $value)
        {
            $this->setAttribute($key, $value);
        }
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function getMandateHub(): string {
        return $this->mandateHub;
    }

    public function getStatus(): string {
        return $this->getAttribute(self::STATUS);
    }
}
