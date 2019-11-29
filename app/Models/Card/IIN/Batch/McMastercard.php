<?php


namespace RZP\Models\Card\IIN\Batch;

use RZP\Models\Bank;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Card\Network;

class McMastercard extends Base
{
    const COMPANY_ID          =  'COMPANY_ID';
    const COMPANY_NAME        =  'COMPANY_NAME';
    const ICA                 =  'ICA';
    const ACCOUNT_RANGE_FROM  =  'ACCOUNT_RANGE_FROM';
    const ACCOUNT_RANGE_TO    =  'ACCOUNT_RANGE_TO';
    const BRAND_PRODUCT_CODE  =  'BRAND_PRODUCT_CODE';
    const BRAND_PRODUCT_NAME  =  'BRAND_PRODUCT_NAME';
    const ACCEPTANCE_BRAND    =  'ACCEPTANCE_BRAND';
    const COUNTRY             =  'COUNTRY';
    const REGION              =  'REGION';

    protected $accountFrom;
    protected $accountTo;
    protected $issuerName;
    protected $issuerId;
    protected $country;
    protected $productType;
    protected $productCode;
    protected $cardTypeBrand;

    protected $rules = [
        self::ACCOUNT_RANGE_FROM  => 'required|digits_between:6,10',
        self::ACCOUNT_RANGE_TO    => 'required|digits_between:6,10',
        self::COMPANY_ID          => 'sometimes',
        self::COMPANY_NAME        => 'sometimes',
        self::ACCEPTANCE_BRAND    => 'sometimes',
        self::ICA                 => 'sometimes',
        self::BRAND_PRODUCT_CODE  => 'sometimes',
        self::BRAND_PRODUCT_NAME  => 'sometimes',
        self::REGION              => 'sometimes',
        self::COUNTRY             => 'sometimes',
        self::IDEMPOTENT_ID       => 'sometimes|string|max:20',
    ];

    const IIN_TYPE_MAPPING = [
        'MCC' => Type::CREDIT,
        'DMC' => Type::DEBIT,
        'MSI' => Type::DEBIT,
        'PVL' => Type::DEBIT,
        'CIR' => Type::DEBIT,
    ];

    public function preprocess($entry)
    {
        parent::preprocess($entry);

        $this->accountFrom      = $entry[self::ACCOUNT_RANGE_FROM];
        $this->accountTo        = $entry[self::ACCOUNT_RANGE_TO];
        $this->issuerName       = $entry[self::COMPANY_NAME];
        $this->issuerId         = $entry[self::COMPANY_ID];
        $this->country          = $entry[self::COUNTRY];
        $this->productType      = $entry[self::BRAND_PRODUCT_NAME];
        $this->productCode      = $entry[self::BRAND_PRODUCT_CODE];
        $this->cardTypeBrand    = $entry[self::ACCEPTANCE_BRAND];
    }

    public function shouldSkip()
    {
        return false;
    }

    public function getIinMin()
    {
        $iinMin = substr($this->accountFrom, 0, 6);

        return $iinMin;

    }

    public function getIinMax()
    {
        $iinMax = substr($this->accountTo, 0, 6);

        return $iinMax;

    }

    public function getType()
    {
        $type = self::IIN_TYPE_MAPPING[$this->cardTypeBrand] ?? null;

        return $type;
    }

    public function getSubType()
    {
        //TODO
    }

    public function getCountry()
    {
        $country = IIN\Country::COUNTRY_ALPHA3_TO_ALPHA2[$this->country] ?? $this->country;

        if (empty(trim($country)) === true)
        {
            return null;
        }

        return $country;

    }

    public function getIssuer()
    {
        return $this->issuerId;
    }

    public function getNetworkCode()
    {
        return Network::MC;
    }

    public function getCategory()
    {
        //TODO
    }

    public function getMessageType()
    {
        //Message Type is not  provided as of now
    }

    public function getProductCode()
    {
        $productCode = $this->productCode;

        return $productCode;
    }

    public function getIssuerName()
    {
        $issuer = $this->getIssuer();

        if (Bank\IFSC::exists($issuer) === true)
        {
            return Bank\Name::getName($issuer);
        }

        return $this->issuerName;
    }
}
