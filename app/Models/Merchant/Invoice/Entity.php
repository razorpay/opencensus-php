<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const INVOICE_NUMBER    = 'invoice_number';
    const MONTH             = 'month';
    const YEAR              = 'year';
    const GSTIN             = 'gstin';
    const TYPE              = 'type';
    const AMOUNT            = 'amount';
    const TAX               = 'tax';
    const AMOUNT_DUE        = 'amount_due';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    protected $entity = 'merchant_invoice';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::AMOUNT,
        self::TAX,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::AMOUNT,
        self::TAX,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::TAX,
        self::AMOUNT_DUE,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::TAX           => 'int',
        self::AMOUNT_DUE    => 'int',
    ];

    protected $defaults = [
        self::AMOUNT        => 0,
        self::TAX           => 0,
        self::AMOUNT_DUE    => 0,
    ];

    protected static $generators = [
        self::INVOICE_NUMBER,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function getAmountDue()
    {
        return $this->getAttribute(self::AMOUNT_DUE);
    }

    public function getInvoiceNumber()
    {
        return $this->getAttribute(self::INVOICE_NUMBER);
    }

    public function getYear()
    {
        return $this->getAttribute(self::YEAR);
    }

    public function getMonth()
    {
        return $this->getAttribute(self::MONTH);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getGstin()
    {
        return $this->getAttribute(self::GSTIN);
    }

    protected function generateInvoiceNumber()
    {
        $merchatId = $this->merchant->getId();

        $year = $this->getYear();

        $month = $this->getMonth();

        $dateString = Carbon::createFromDate($year, $month, 1, Timezone::IST)->format('m/Y');

        $this->setAttribute(self::INVOICE_NUMBER, $merchatId . '/' . $dateString);
    }
}