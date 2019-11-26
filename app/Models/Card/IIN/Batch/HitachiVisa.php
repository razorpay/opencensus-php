<?php


namespace RZP\Models\Card\IIN\Batch;

use RZP\Models\Bank;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Card\Network;

class HitachiVisa extends Base
{
    const ROW = 'row';

    protected $row;

    protected $rules = [
        self::ROW           => 'required|string|max:200',
        self::IDEMPOTENT_ID => 'sometimes|string|max:20',
    ];

    const IIN_TYPE_MAPPING = [
        'D' => Type::DEBIT,
        'C' => Type::CREDIT,
        'P' => Type::PREPAID,
        'H' => Type::CREDIT,
        'R' => Type::DEBIT,
    ];

    const IIN_CATEGORY_MAPPING = [
        'B' =>	IIN\Category::BUSINESS,
        'H' =>	IIN\Category::INFINITE,
        'J' =>	IIN\Category::PLATINUM,
        'K' =>	IIN\Category::SIGNATURE,
        'O' =>	IIN\Category::SIGNATURE_BUSINESS,
        'R' =>	IIN\Category::CORPORATE,
        'S' =>	IIN\Category::PURCHASING,
    ];

    public function preprocess($entry)
    {
        parent::preprocess($entry);

        $this->row = $entry[self::ROW];
    }

    public function shouldSkip()
    {
        return false;
    }

    public function getIinMin()
    {
        $iinMin = substr($this->row, 12, 6);

        return $iinMin;
    }

    public function getIinMax()
    {
        $iinMax = substr($this->row, 0, 6);

        return $iinMax;
    }

    public function getType()
    {
        $cardType = substr($this->row,69 , 1);

        $type = self::IIN_TYPE_MAPPING[$cardType] ?? null;

        return $type;
    }

    public function getSubType()
    {
        //Sub Type is not provided as of now
    }

    public function getCountry()
    {
        $country = trim(substr($this->row, 48, 2));

        if (empty($country) === true)
        {
            return null;
        }

        return $country;
    }

    public function getIssuer()
    {
        // no issuer information is provided as of now
    }

    public function getNetworkCode()
    {
        $network = Network::VISA;

        return $network;
    }

    public function getCategory()
    {
        $categoryID = substr($this->row, 58, 1);

        $category = self::IIN_CATEGORY_MAPPING[$categoryID] ?? null;

        return $category;
    }

    public function getMessageType()
    {
        // message type is not provided as of now
    }

    public function getProductCode()
    {
        $productCode = trim(substr($this->row, 58, 2));

        return $productCode;
    }

    public function getIssuerName()
    {
        $issuer = $this->getIssuer();

        if (Bank\IFSC::exists($issuer) === true)
        {
            return Bank\Name::getName($issuer);
        }

        return null;
    }
}
