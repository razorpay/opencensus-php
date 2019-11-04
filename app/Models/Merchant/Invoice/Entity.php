<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Merchant\Balance\Type as BalanceType;

class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const INVOICE_NUMBER    = 'invoice_number';
    const MONTH             = 'month';
    const YEAR              = 'year';
    const GSTIN             = 'gstin';
    const TYPE              = 'type';
    const AMOUNT            = 'amount';
    const TAX               = 'tax';
    const DESCRIPTION       = 'description';
    const AMOUNT_DUE        = 'amount_due';
    const BALANCE_ID        = 'balance_id';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    const INVOICE_NUMBER_LENGTH     =   255;

    protected $entity = 'merchant_invoice';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::DESCRIPTION,
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
        self::DESCRIPTION,
        self::AMOUNT,
        self::TAX,
        self::BALANCE_ID,
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
        self::DESCRIPTION   => null,
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

    public function getDescription()
    {
        $description = $this->getAttribute(self::DESCRIPTION);

        if (empty($description) === false)
        {
            return $description;
        }

        $type = $this->getAttribute(self::TYPE);

        return Type::getDescriptionFromType($type);
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

    public function setGstin($gstin)
    {
        $this->setAttribute(self::GSTIN, $gstin);
    }

    public function generateInvoiceNumber(int $month, int $year, string $balanceType = BalanceType::PRIMARY)
    {
        $dateString = Carbon::createFromDate($year, $month, 1, Timezone::IST)->format('my');

        $invoiceNumber = $this->merchant->getInvoiceCode() . $dateString;

        if ($balanceType === BalanceType::BANKING)
        {
            $invoiceNumber = $invoiceNumber . Constants::RZPX;
        }

        $this->setAttribute(self::INVOICE_NUMBER, $invoiceNumber);
    }
}
