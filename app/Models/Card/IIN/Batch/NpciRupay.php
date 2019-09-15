<?php

namespace RZP\Models\Card\IIN\Batch;

use RZP\Models\Card\IIN;
use RZP\Models\Card\Type;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN\MessageType;

class NpciRupay extends Base
{
    const ROW                   = 'row';

    protected $row;

    protected $rules = [
        self::ROW   => 'required|string|max:62'
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

    public function getIin()
    {
        $iin = substr($this->row, 11, 6);

        return $iin;
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
        $country = substr($this->row, 47, 2);

        return $country;
    }

    public function getIssuer()
    {
        $network = $this->getNetworkCode();

        if ($network !== Network::RUPAY)
        {
            return;
        }

        $issuer = substr($this->row, 0, 4);

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
        // TODO
    }

    public function getMessageType()
    {
        $messageTypeValue = substr($this->row, 31, 1);

        $messageType = self::IIN_MESSAGE_TYPE_MAPPING[$messageTypeValue] ?? null;

        return $messageType;
    }
}
