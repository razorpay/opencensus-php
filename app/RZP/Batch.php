<?php

namespace App\RZP;

use Config;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

class Batch extends Entity
{
    const BATCH_FILE_URL = 'batches';

    public function fetch($id)
    {
        return parent::fetch($id);
    }

    public function all($input = array())
    {
        return parent::all($input);
    }

    protected function getApiCredentials($mode, $merchantId)
    {
        $id = 'rzp_' . $mode . '_' . $merchantId;

        $secret = Config::get('api.auth_pass');

        return [$id, $secret];
    }

    protected function getEntityUrl()
    {
        return 'batches/';
    }
}
