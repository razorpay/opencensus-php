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

    public function uploadRefundFile($input)
    {
        $relativeUrl = 'batches';

        return $this->request('POST', $relativeUrl, $input);
    }

    public function downloadRefundFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/download';

        return $this->request('GET', $relativeUrl);
    }

    public function retryRefundFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/retry';

        return $this->request('POST', $relativeUrl);
    }
}
