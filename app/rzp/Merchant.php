<?php

namespace RZP;

class Merchant extends Entity
{
    public function create($params = null)
    {
        return parent::create($params);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function keys()
    {
        $entity = new Key;

        $entity->merchant_id = $this->id;

        return $entity;
    }

    public function activate()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/activate';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * Enables live transactions for merchant
     */
    public function enable()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/live/enable';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * disable live transactions for merchant
     */
    public function disable()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/live/disable';

        return $this->request('POST', $relativeUrl);
    }

    /**
     * Changes Payment methods for merchant
     */
    public function editMethods($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/methods';

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function edit($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id;

        return $this->request('PUT', $relativeUrl, $params);
    }

    public function fetchPricing()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/pricing';

        return $this->request('GET', $relativeUrl);
    }

    public function setPricing($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/pricing';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchTerminals()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/terminals';

        return $this->request('GET', $relativeUrl);
    }

    public function setTerminal($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/terminals';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBanks()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/banks';

        return $this->request('GET', $relativeUrl);
    }

    public function setBanks($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/banks';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBankAccount()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/bank_account';

        return $this->request('GET', $relativeUrl);
    }

    public function setBankAccount($params)
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/bank_account';

        return $this->request('POST', $relativeUrl, $params);
    }

    public function fetchBalance()
    {
        $relativeUrl = $this->getEntityUrl().$this->id.'/balance';

        return $this->request('GET', $relativeUrl);
    }

    public function generateBeneficiaryFile()
    {
        $relativeUrl = $this->getEntityUrl().'beneficiary/file';

        return $this->request('GET', $relativeUrl);
    }
}
