<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const INVOICE_NUMBER        = 'invoice_number';
    const MONTH                 = 'month';
    const YEAR                  = 'year';
    const GSTIN                 = 'gstin';
    const TYPE                  = 'type';
    const DOCUMENT_TYPE         = 'document_type';
    const STATUS                = 'status';
    const GSP_STATUS            = 'gsp_status';
    const GSP_ERROR             = 'gsp_error';
    const RZP_ERROR             = 'rzp_error';
    const GSP_IRN               = 'gsp_irn';
    const GSP_SIGNED_INVOICE    = 'gsp_signed_invoice';
    const GSP_SIGNED_QR_CODE    = 'gsp_signed_qr_code';
    const GSP_QR_CODE_URL       = 'gsp_qr_code_url';
    const GSP_E_INVOICE_PDF     = 'gsp_e_invoice_pdf';
    const ATTEMPTS              = 'attempts';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    const TYPE_LENGTH           = 20;
    const DOCUMENT_TYPE_LENGTH  = 5;

    protected $entity = 'merchant_e_invoice';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::DOCUMENT_TYPE,
        self::STATUS,
        self::GSP_STATUS,
        self::GSP_ERROR,
        self::RZP_ERROR,
        self::GSP_IRN,
        self::GSP_SIGNED_INVOICE,
        self::GSP_SIGNED_QR_CODE,
        self::GSP_QR_CODE_URL,
        self::GSP_E_INVOICE_PDF,
        self::ATTEMPTS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::DOCUMENT_TYPE,
        self::STATUS,
        self::GSP_STATUS,
        self::GSP_ERROR,
        self::RZP_ERROR,
        self::GSP_IRN,
        self::GSP_SIGNED_INVOICE,
        self::GSP_SIGNED_QR_CODE,
        self::GSP_QR_CODE_URL,
        self::GSP_E_INVOICE_PDF,
        self::ATTEMPTS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
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

    /**
     * getType returns type of invoice like PG, BANKING etc.
     * */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /**
     * getDocumentType returns the type of document
     * invoice - INV
     * credit-note - CRN
     * debit-note - DBN
     * */
    public function getDocumentType()
    {
        return $this->getAttribute(self::DOCUMENT_TYPE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function getGspStatus()
    {
        return $this->getAttribute(self::GSP_STATUS);
    }

    public function setGspStatus(string $status)
    {
        return $this->setAttribute(self::GSP_STATUS, $status);
    }

    public function getGspError()
    {
        return $this->getAttribute(self::GSP_ERROR);
    }

    public function setGspError($error)
    {
        return $this->setAttribute(self::GSP_ERROR, $error);
    }

    public function getRzpError()
    {
        return $this->getAttribute(self::RZP_ERROR);
    }

    public function setRzpError(string $error)
    {
        return $this->setAttribute(self::RZP_ERROR, $error);
    }

    public function getGspIrn()
    {
        return $this->getAttribute(self::GSP_IRN);
    }

    public function setGspIrn(string $irn)
    {
        return $this->setAttribute(self::GSP_IRN, $irn);
    }

    public function getGspSignedInvoice()
    {
        return $this->getAttribute(self::GSP_SIGNED_INVOICE);
    }

    public function setGspSignedInvoice(string $signedInvoice)
    {
        return $this->setAttribute(self::GSP_SIGNED_INVOICE, $signedInvoice);
    }

    public function getGspSignedQrCode()
    {
        return $this->getAttribute(self::GSP_SIGNED_QR_CODE);
    }

    public function setGspSignedQrCode(string $signedQrCode)
    {
        return $this->setAttribute(self::GSP_SIGNED_QR_CODE, $signedQrCode);
    }

    public function getGspQRCodeUrl()
    {
        return $this->getAttribute(self::GSP_QR_CODE_URL);
    }

    public function setGspQRCodeUrl(string $qrCodeUrl)
    {
        return $this->setAttribute(self::GSP_QR_CODE_URL, $qrCodeUrl);
    }

    public function getGspEInvoicePdf()
    {
        return $this->getAttribute(self::GSP_E_INVOICE_PDF);
    }

    public function setGspEInvoicePdf(string $eInvoicePdf)
    {
        return $this->setAttribute(self::GSP_E_INVOICE_PDF, $eInvoicePdf);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function setAttempts(int $attempt)
    {
        return $this->setAttribute(self::ATTEMPTS, $attempt);
    }

    public function getGstin()
    {
        return $this->getAttribute(self::GSTIN);
    }

    public function setGstin(string $gstin)
    {
        return $this->setAttribute(self::GSTIN, $gstin);
    }
}