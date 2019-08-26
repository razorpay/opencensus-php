<?php

namespace RZP\Models\Card\IIN\Batch;

use RZP\Models\Card\Network;
use RZP\Models\Card\IIN;
use RZP\Models\Base\Core as BaseCore;

abstract class Base extends BaseCore
{
    protected $input;

    protected $iin;

    protected $entry;

    public function __construct()
    {
        parent::__construct();

        $this->iinService = new IIN\Service;

        $this->input = [];
    }

    public function preprocess(array $entry)
    {
        $this->entry = $entry;
    }

    public function process()
    {
        $this->parseEntry();

        $this->iinService->addOrUpdate($this->iin, $this->input);
    }

    protected function parseEntry()
    {
        $this->iin = $this->getIin();

        $this->setNetwork();

        $this->setIssuer();

        $this->setType();

        $this->setSubType();

        $this->setCountry();

        $this->setCategory();

        $this->setMessageType();
    }

    protected function setType()
    {
        $value = $this->getType();

        if ($value !== null)
        {
            $this->input[IIN\Entity::TYPE] = $value;
        }
    }

    protected function setSubType()
    {
        $value = $this->getSubType();

        if ($value !== null)
        {
            $this->input[IIN\Entity::SUBTYPE] = $value;
        }
    }

    protected function setCountry()
    {
        $value = $this->getCountry();

        if ($value !== null)
        {
            $this->input[IIN\Entity::COUNTRY] = $value;
        }
    }

    protected function setIssuer()
    {
        $value = $this->getIssuer();

        if ($value !== null)
        {
            $this->input[IIN\Entity::ISSUER] = $value;
        }
    }

    protected function setNetwork()
    {
        $value = $this->getNetworkCode();

        if ($value !== null)
        {
            $networkName = Network::getFullName($value);

            $this->input[IIN\Entity::NETWORK] = $networkName;
        }
    }

    protected function setCategory()
    {
        $value = $this->getCategory();

        if ($value !== null)
        {
            $this->input[IIN\Entity::CATEGORY] = $value;
        }
    }

    protected function setMessageType()
    {
        $value = $this->getMessageType();

        if ($value !== null)
        {
            $this->input[IIN\Entity::MESSAGE_TYPE] = $value;
        }
    }

    abstract function getIin();

    abstract function getType();

    abstract function getSubType();

    abstract function getCountry();

    abstract function getIssuer();

    abstract function getNetworkCode();

    abstract function getCategory();

    abstract function getMessageType();
}
