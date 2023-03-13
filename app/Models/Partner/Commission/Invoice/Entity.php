<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Exception\LogicException;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\HardDeletes;

class Entity extends Base\PublicEntity
{
    use HasBalance;
    use HardDeletes;

    const ID                  = 'id';
    const MERCHANT_ID         = 'merchant_id';
    const MONTH               = 'month';
    const YEAR                = 'year';
    const GROSS_AMOUNT        = 'gross_amount';
    const TAX_AMOUNT          = 'tax_amount';
    const STATUS              = 'status';
    const BALANCE_ID          = 'balance_id';
    const NOTES               = 'notes';
    const TNC                 = 'tnc';
    const ACTION              = 'action';
    const MAX_AUTO_APPROVAL_AMOUNT = 5000000;
    /**
     * Prefix for pdf file name
     */
    const PDF_PREFIX               = 'pdfs/commission/';

    const LINE_ITEMS               = 'line_items';
    const PDF                      = 'pdf';

    const REGENERATE_IF_EXISTS = 'regenerate_if_exists';
    const FORCE_REGENERATE     = 'force_regenerate';

    protected $entity = 'commission_invoice';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::STATUS       => Status::ISSUED,
        self::GROSS_AMOUNT => 0,
        self::TAX_AMOUNT   => 0,
    ];

    protected $casts = [
        self::GROSS_AMOUNT => 'int',
        self::TAX_AMOUNT   => 'int',
    ];

    protected $fillable = [
        self::MONTH,
        self::YEAR,
        self::TNC,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::MONTH,
        self::YEAR,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::STATUS,
        self::NOTES,
        self::TNC,
        self::LINE_ITEMS,
        self::PDF,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $embeddedRelations = [
        self::LINE_ITEMS,
    ];

    protected $publicSetters = [
        self::PDF,
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function lineItems()
    {
        return $this->morphMany('RZP\Models\LineItem\Entity', 'entity');
    }

    public function setAmountsToNull()
    {
        $this->setAttribute(self::GROSS_AMOUNT, null);
        $this->setAttribute(self::TAX_AMOUNT, null);
    }

    public function setGrossAmount(int $amount)
    {
        $this->setAttribute(self::GROSS_AMOUNT, $amount);
    }

    public function setTaxAmount(int $amount)
    {
        $this->setAttribute(self::TAX_AMOUNT, $amount);
    }

    public function setStatus(string $next)
    {
        $current = $this->getStatus();

        if (Status::isValidStateTransition($current, $next) === false)
        {
            throw new LogicException(
                'Invalid status transition',
                null,
                [
                    'current' => $current,
                    'next'    => $next,
                ]
            );
        }

        $this->setAttribute(self::STATUS, $next);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getGrossAmount()
    {
        return $this->getAttribute(self::GROSS_AMOUNT);
    }

    public function getTaxAmount()
    {
        return $this->getAttribute(self::TAX_AMOUNT);
    }

    public function getMonth()
    {
        return $this->getAttribute(self::MONTH);
    }

    public function getYear()
    {
        return $this->getAttribute(self::YEAR);
    }

    public function isIssued(): bool
    {
        return ($this->getStatus() === Status::ISSUED);
    }

    /**
     * Returns string to be used a pdf file path in s3/local store.
     * Format: pdfs/commission/{invoiceId}_{epoch}
     *
     * @return string
     */
    public function getPdfFilename(): string
    {
        return self::PDF_PREFIX . $this->getId() . '_' . time();
    }

    public function pdf()
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::COMMISSION_INVOICE)
                    ->latest()
                    ->first();
    }

    public function file()
    {
        return $this->morphOne('RZP\Models\FileStore\Entity', 'entity')
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::COMMISSION_INVOICE)
                    ->latest();
    }

    public function setPublicPdfAttribute(array & $array)
    {
        $pdf = $this->pdf();

        if (empty($pdf) === false)
        {
            $array[self::PDF] = $pdf->toArrayPublic();
        }
    }

    public function files()
    {
        return $this->morphMany('RZP\Models\FileStore\Entity', 'entity');
    }

    public function getCurrency()
    {
        return $this->merchant->getCurrency();
    }
}
