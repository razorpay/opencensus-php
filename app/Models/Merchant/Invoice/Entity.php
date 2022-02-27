<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
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

    const INVOICE_NUMBER_LENGTH = 255;
    const ACCOUNT_NUMBER        = 'account_number';
    const SEND_EMAIL            = 'send_email';
    const TO_EMAILS             = 'to_emails';

    // Slack channel for alerts
    const P0_PP_ALERTS      = 'p0_pp_alerts';

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
        self::ACCOUNT_NUMBER,
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

    protected $public = [
        self::MONTH,
        self::YEAR,
        self::AMOUNT,
        self::TAX,
        self::ACCOUNT_NUMBER,
    ];

    protected $publicSetters = [
        self::ACCOUNT_NUMBER,
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

    /**
     * Invoice number generator for merchant_invoice
     *
     * Format for Invoice Number on PG
     * <12 chars invoice_code> + <mmyy>
     * Where Invoice code = < first 8 chars of MID> + <last 4 chars of MID>
     *
     * Format for Invoice Number on X
     * <11 chars of MID> + `-` + <mmyy>
     *
     * Relevant Slack threads for information :
     * https://razorpay.slack.com/archives/CE4DMABE3/p1573094789005300
     * https://razorpay.slack.com/archives/CE4DMABE3/p1573012152427700
     *
     * @param int    $month
     * @param int    $year
     * @param string $balanceType
     */
    public function generateInvoiceNumber(int $month, int $year, string $balanceType = BalanceType::PRIMARY)
    {
        $dateString = Carbon::createFromDate($year, $month, 1, Timezone::IST)->format('my');

        $invoiceNumber = $this->merchant->getInvoiceCode();

        if ($balanceType === BalanceType::BANKING)
        {
            $invoiceNumber = substr($this->merchant->getId(), 0, Constants::INVOICE_CODE_LENGTH_FOR_X);

            $invoiceNumber = strtoupper($invoiceNumber);

            $invoiceNumber = $invoiceNumber . Constants::X_INVOICE_SEPARATOR;
        }

        $invoiceNumber = $invoiceNumber . $dateString;

        $this->setAttribute(self::INVOICE_NUMBER, $invoiceNumber);
    }

    public function setPublicAccountNumberAttribute(array & $attributes)
    {
        $attributes[self::ACCOUNT_NUMBER] = $this->getAccountNumberAttribute();
    }
}
