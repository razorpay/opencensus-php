<?php

namespace RZP\Models\Card\IIN\Batch;

use RZP\Exception;
use RZP\Base\JitValidator;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN;
use RZP\Models\Base\Core as BaseCore;

abstract class Base extends BaseCore
{
    const IDEMPOTENT_ID                   = 'idempotent_id';

    protected $input;

    protected $iinMin;

    protected $iinMax;

    protected $entry;

    protected $rules = [];

    public function __construct()
    {
        parent::__construct();

        $this->iinService = new IIN\Service;
    }

    public function preprocess(array $entry)
    {
        $this->entry = $entry;

        $this->input = [];

        $this->validate();
    }

    public function process()
    {
        if ($this->shouldSkip() === true)
        {
            return ['skipped' => true];
        }

        $this->parseEntry();

        $this->validateParsedData();

        $success = $this->iinService->addOrUpdateMultiple($this->iinMin,$this->iinMax,$this->input);

        return $success;
    }

    public function shouldSkip()
    {
        return false;
    }

    protected function validate()
    {
        (new JitValidator)->rules($this->rules)
                          ->input($this->entry)
                          ->validate();
    }

    protected function parseEntry()
    {
        $this->iinMin = $this->getIinMin();

        $this->iinMax = $this->getIinMax();

        $this->setNetwork();

        $this->setIssuer();

        $this->setIssuerName();

        $this->setType();

        $this->setSubType();

        $this->setProductCode();

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

    protected function setProductCode()
    {
        $value = $this->getProductCode();

        if ($value !== null)
        {
            $this->input[IIN\Entity::PRODUCT_CODE] = $value;
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

    public function setIssuerName()
    {
        $value = $this->getIssuerName();

        if ($value !== null)
        {
            $this->input[IIN\Entity::ISSUER_NAME] = $value;
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

    protected function validateParsedData()
    {
        if ((isset($this->iinMin) && isset($this->iinMax)  === false) || ($this->iinMax < $this->iinMin))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid\Empty IIN Range given',
                [
                    'IIN Min' => $this->iinMin,
                    'IIN Max' => $this->iinMax
                ]
            );
        }

        if ((isset($this->input[IIN\Entity::COUNTRY]) === true) and
            (strlen($this->input[IIN\Entity::COUNTRY]) !== 2))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Country Code: '.$this->input[IIN\Entity::COUNTRY]);
        }
    }

    abstract function getIinMin();

    abstract function getIinMax();

    abstract function getType();

    abstract function getSubType();

    abstract function getCountry();

    abstract function getIssuer();

    abstract function getIssuerName();

    abstract function getNetworkCode();

    abstract function getCategory();

    abstract function getMessageType();

    abstract function getProductCode();
}
