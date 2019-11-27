<?php

namespace RZP\Models\Card\IIN\Batch;

use RZP\Models\Bank;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN\MessageType;

class NpciRupay extends Base
{
    const ROW                   = 'row';

    protected $row;

    protected $rules = [
        self::ROW           => 'required|string|max:62',
        self::IDEMPOTENT_ID => 'sometimes|string|max:20'
    ];

    const IIN_TYPE_MAPPING = [
        '01' => Type::DEBIT,
        '02' => Type::CREDIT,
        '03' => Type::PREPAID,
    ];

    const IIN_NETWORK_MAPPING = [
        '01' => Network::RUPAY,
        '04' => Network::DICL,
        '05' => Network::JCB,
        '07' => Network::UNP,
    ];

    const IIN_MESSAGE_TYPE_MAPPING = [
        'S' => MessageType::SMS,
        'D' => MessageType::DMS,
    ];

    const IIN_DEBIT_CATEGORY_MAPPING = [
        "01" => [
            "01" => IIN\Category::CLASSIC,
            "03" => IIN\Category::PLATINUM,
            "21" => IIN\Category::SELECT,
            "22" => IIN\Category::CLASSIC,
            "24" => IIN\Category::PLATINUM,
            "25" => IIN\Category::CLASSIC,
        ],
        "05" => [
            "01" => IIN\Category::CLASSIC,
        ],
    ];

    const IIN_CREDIT_CATEGORY_MAPPING = [
        "01" => [
            "01" => IIN\Category::CLASSIC,
            "03" => IIN\Category::PLATINUM,
            "21" => IIN\Category::SELECT,
        ],
        "04" => [
            "21" => IIN\Category::SELECT,
        ],
    ];

    const IIN_PREPAID_CATEGORY_MAPPING = [
        "01" => [
            "01" => IIN\Category::CLASSIC,
            "12" => IIN\Category::CLASSIC,
            "15" => IIN\Category::CLASSIC,
        ],
        "05" => [
            "01" => IIN\Category::CLASSIC,
            "03" => IIN\Category::PLATINUM,
            "12" => IIN\Category::CLASSIC,
            "15" => IIN\Category::CLASSIC,
        ],
        "06" => [
            "12" => IIN\Category::CLASSIC,
        ]
    ];



    public function preprocess($entry)
    {
        parent::preprocess($entry);

        $this->row = $entry[self::ROW];
    }

    public function shouldSkip()
    {
        if (substr($this->row, 0, 3) === 'TRL')
        {
            return true;
        }

        return false;
    }

    public function getIinMin()
    {
        $iinMin = substr($this->row, 11, 6);

        return $iinMin;
    }

    public function getIinMax()
    {
        $iinMax = substr($this->row, 20, 6);

        return $iinMax;
    }

    public function getType()
    {
        $cardType = substr($this->row, 32, 2);

        $type = self::IIN_TYPE_MAPPING[$cardType] ?? null;

        return $type;
    }

    public function getSubType()
    {
        // TODO
    }

    public function getCountry()
    {
        $country = trim(substr($this->row, 47, 2));

        if (empty($country) === true)
        {
            return null;
        }

        return $country;
    }

    public function getIssuer()
    {
        $network = $this->getNetworkCode();

        if ($network !== Network::RUPAY)
        {
            return;
        }

        $issuer = trim(substr($this->row, 0, 4));

        //Since RuPay files have this mapping wrong
        $issuer = ($issuer === "IDFC") ? "IDFB" : $issuer;

        if (empty($issuer) === true)
        {
            return null;
        }

        return $issuer;
    }

    public function getNetworkCode()
    {
        $networkValue = substr($this->row, 41, 2);

        $network = self::IIN_NETWORK_MAPPING[$networkValue] ?? null;

        return $network;
    }

    public function getCategory()
    {
        $network = $this->getNetworkCode();

        if ($network !== Network::RUPAY)
        {
            return;
        }

        $type = $this->getType();

        $subCategory = substr($this->row, 34, 2);

        $variant = substr($this->row, 36, 2);

        switch ($type)
        {
            case Type::DEBIT:
                $category = self::IIN_DEBIT_CATEGORY_MAPPING[$subCategory][$variant] ?? null;
                break;
            case Type::CREDIT:
                $category = self::IIN_CREDIT_CATEGORY_MAPPING[$subCategory][$variant] ?? null;
                break;
            case Type::PREPAID:
                $category = self::IIN_PREPAID_CATEGORY_MAPPING[$subCategory][$variant] ?? null;
                break;
            default :
                return;
        }

        return $category;
    }

    public function getMessageType()
    {
        $messageTypeValue = substr($this->row, 31, 1);

        $messageType = self::IIN_MESSAGE_TYPE_MAPPING[$messageTypeValue] ?? null;

        return $messageType;
    }

    public function getProductCode()
    {
        // TODO
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
