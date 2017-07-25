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
        if ($entity === 'broking')
        {
            $relativeUrl = "reports/transaction/broking";
        }
        else
        {
            $relativeUrl = "reports/$entity";
        }

        return $this->longRequest('GET', $relativeUrl, $params);
    }

    public function generateEntityReportFile($entity, $params)
    {
        $relativeUrl = "reports/$entity/file";

        if ($entity === 'dsp_report')
        {
            $relativeUrl = "reports/transaction/dsp";
        }
        else if ($entity === 'rpp_report')
        {
            $relativeUrl = "reports/order/rpp";
        }

        return $this->longRequest('GET', $relativeUrl, $params);
    }

    public function getInvoiceData($params)
    {
        $relativeUrl = "reports/invoice";

        return $this->request('GET', $relativeUrl, $params);
    }
}
