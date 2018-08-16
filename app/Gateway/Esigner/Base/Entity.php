<?php

namespace RZP\Gateway\Esigner\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const GATEWAY               = 'gateway';
    const ACTION                = 'action';
    const MANDATE_ID            = 'mandate_id';
    const STATUS                = 'status';
    const ACCOUNT_TYPE          = 'account_type';
    const ACCOUNT_NUMBER        = 'account_number';
    const AGENT_TYPE            = 'agent_type';
    const AGENT_ID              = 'agent_id';
    const AGENT_NAME            = 'agent_name';
    const SEQUENCE_TYPE         = 'sequence_type';
    const FREQUENCY_TYPE        = 'frequency_type';
    const START_DATE            = 'start_date';
    const END_DATE              = 'end_date';
    const AMOUNT_TYPE           = 'amount_type';
    const AMOUNT                = 'amount';
    const CATEGORY_CODE         = 'category_code';
    const ERROR_CODE            = 'error_code';
    const ERROR_MESSAGE         = 'error_message';

    protected $entity = 'esigner';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY,
        self::ACTION,
        self::MANDATE_ID,
        self::STATUS,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_NUMBER,
        self::AGENT_TYPE,
        self::AGENT_ID,
        self::AGENT_NAME,
        self::SEQUENCE_TYPE,
        self::FREQUENCY_TYPE,
        self::START_DATE,
        self::END_DATE,
        self::AMOUNT_TYPE,
        self::AMOUNT,
        self::CATEGORY_CODE,
        self::ERROR_CODE,
        self::ERROR_MESSAGE,
    ];

    protected $fillable = [
        self::PAYMENT_ID,
        self::GATEWAY,
        self::ACTION,
        self::MANDATE_ID,
        self::STATUS,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_NUMBER,
        self::AGENT_TYPE,
        self::AGENT_ID,
        self::AGENT_NAME,
        self::SEQUENCE_TYPE,
        self::FREQUENCY_TYPE,
        self::START_DATE,
        self::END_DATE,
        self::AMOUNT_TYPE,
        self::AMOUNT,
        self::CATEGORY_CODE,
        self::ERROR_CODE,
        self::ERROR_MESSAGE,
    ];

    protected $defaults = [
        self::PAYMENT_ID      => null,
        self::GATEWAY         => null,
        self::ACTION          => null,
        self::MANDATE_ID      => null,
        self::STATUS          => null,
        self::ACCOUNT_TYPE    => null,
        self::ACCOUNT_NUMBER  => null,
        self::AGENT_TYPE      => null,
        self::AGENT_ID        => null,
        self::AGENT_NAME      => null,
        self::SEQUENCE_TYPE   => null,
        self::FREQUENCY_TYPE  => null,
        self::START_DATE      => null,
        self::END_DATE        => null,
        self::AMOUNT_TYPE     => null,
        self::AMOUNT          => null,
        self::CATEGORY_CODE   => null,
        self::ERROR_CODE      => null,
        self::ERROR_MESSAGE   => null,
    ];

    public function payment()
    {
        return $this->belongsTo(\RZP\Models\Payment\Entity::class);
    }

    public function setGateway(string $gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }
}
