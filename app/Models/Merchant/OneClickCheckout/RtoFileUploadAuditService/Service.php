<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoFileUploadAuditService;

use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const CREATE_FILE_UPLOAD_AUDIT    = 'create_file_upload_audit';
    const LIST_FILE_UPLOAD_AUDIT      = 'list_file_upload_audits';
    const PATH                        = 'path';
    const MERCHANT_ID                 = 'merchant_id';

    const PARAMS = [
        self::CREATE_FILE_UPLOAD_AUDIT  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_file_upload_audits.v1.MerchantFileUploadAuditApi/Create',
        ],
        self::LIST_FILE_UPLOAD_AUDIT  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_file_upload_audits.v1.MerchantFileUploadAuditApi/List',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function create($input,$merchantId)
    {
        $input = $this->addRequestDetails($input,$merchantId);

        $params = self::PARAMS[self::CREATE_FILE_UPLOAD_AUDIT];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function list($input,$merchantId)
    {
        $input = $this->addRequestDetails($input,$merchantId);

        $params = self::PARAMS[self::LIST_FILE_UPLOAD_AUDIT];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    protected function addRequestDetails($input, $merchantId)
    {
        $input['merchant_id'] = $merchantId;
        return $input;
    }
}
