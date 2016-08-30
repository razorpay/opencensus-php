<?php

namespace App\RZP;

class Settlement extends Entity
{
    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($options = array())
    {
        return parent::all($options);
    }

    public function initiate($channel)
    {
        $relativeUrl = $this->getEntityUrl().'initiate/'.$channel;

        return $this->request('POST', $relativeUrl);
    }

    public function getDetails($id)
    {
        $error = $response = null;

        try
        {
            $relativeUrl = $this->getEntityUrl() . $id . '/details';

            $response = $this->request('GET', $relativeUrl)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }
}
