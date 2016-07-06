<?php

namespace App\RZP;

class Transaction extends Entity
{
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all();
    }

    public function generateEntityReport($entity, $params)
    {
        $relativeUrl = "reports/$entity";

        return $this->longRequest('GET', $relativeUrl, $params);
    }

    public function getInvoiceData($params)
    {
        $relativeUrl = "reports/invoice";

        return $this->request('GET', $relativeUrl, $params);
    }

    /**
     * We are matching the Entity\request method, but with an extra
     * timeout being set to 60s
     */
    protected function longRequest($method, $relativeUrl, $data = null)
    {
        // This is an instance of App\RZP\Request now
        // which includes the setOption method
        $request = new Request();
        $request->setOption('timeout', 60);

        $response = $request->request($method, $relativeUrl, $data);

        if ((isset($response['entity'])) and
            ($response['entity'] == $this->getEntity()))
        {
            $this->fill($response);

            return $this;
        }
        else
        {
            return static::buildEntity($response);
        }
    }
}
