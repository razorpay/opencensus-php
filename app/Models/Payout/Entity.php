<?php

namespace RZP\Models\Payout;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Payout;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';
    const METHOD            = 'method';
    const DESTINATION       = 'destination';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const NOTES             = 'notes';
    const FEE               = 'fee';
    const SERVICE_TAX       = 'service_tax';
    const TRANSACTION_ID    = 'transaction_id';
    const STATUS            = 'status';
    const CHANNEL           = 'channel';
    const UTR               = 'utr';
    const FAILURE_REASON    = 'failure_reason';
    const RETURN_UTR        = 'return_utr';

    protected $entity = 'payout';

    protected $table  = \RZP\Constants\Table::PAYOUT;

    protected $generateIdOnCreate = true;

    protected static $sign      = 'pout';

    protected static $generators = array(self::ID);

    protected $fillable = array(
        self::ID,
        self::METHOD,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::NOTES,
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::DESTINATION,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::METHOD,
        self::FEE,
        self::SERVICE_TAX,
        self::TRANSACTION_ID,
        self::STATUS,
        self::CHANNEL,
        self::UTR,
        self::FAILURE_REASON,
        self::RETURN_UTR
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::DESTINATION,
        self::METHOD,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::FEE,
        self::SERVICE_TAX,
        self::STATUS,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::DESTINATION,
        self::CUSTOMER_ID);

    protected $defaults = array(
        self::STATUS            => Status::CREATED,
        self::NOTES             => [],
    );

    protected $amounts = array(
        self::AMOUNT,
        self::FEE,
        self::SERVICE_TAX
    );

    protected $casts = array(
        self::AMOUNT      => 'int',
        self::FEE         => 'int',
        self::SERVICE_TAX => 'int',
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function dest()
    {
        $method = $this->getAttribute(self::METHOD);

        $class = Payout\Method::getEntityClass($method);

        return $this->belongsTo($class, self::DESTINATION);
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setPublicDestinationAttribute(array & $array)
    {
        $method = $array[self::METHOD];

        $entity = Payout\Method::getEntityClass($method);

        $sign = $entity::getIdPrefix();

        $array[self::DESTINATION] = $sign . $array[self::DESTINATION];
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        $sign = Customer\Entity::getIdPrefix();

        $array[self::CUSTOMER_ID] = $sign . $array[self::CUSTOMER_ID];
    }
}