<?php

namespace App\RZP;

class Invoice extends Entity
{
    public function create($params = [])
    {
        return parent::create($params);
    }

    public function edit($id, $params = [])
    {
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('PATCH', $entityUrl, $params);
    }

    public function all($options = [])
    {
        return parent::all($options);
    }

    public function delete($id)
    {
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('DELETE', $entityUrl);
    }

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function sendNotification($id, $medium)
    {
        $relativeUrl = 'invoices/' . $id . '/notify/' . $medium;
        return $this->request('POST', $relativeUrl, []);
    }
}
