<?php

namespace RZP\Services\VendorPortal;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;


class Service
{
    const LIST_VENDOR_INVOICES           = "ListVendorInvoices";
    const CREATE_INVITE                  = "Invite";
    const ACCEPT_INVITE                  = "AcceptInvite";
    const LIST_TDS_CATEGORIES            = "ListTdsCategory";
    const GET_VENDOR_INVOICE             = "GetVendorInvoice";
    const GET_INVOICE_SIGNED_URL         = "GetInvoiceSignedURL";
    const LIST_VENDOR_PORTAL_INVITES     = "ListVendorPortalInvites";
    const UPLOAD_INVOICE                 = "UploadInvoice";
    const GET_OCR_DATA                   = "GetOcrData";
    const CREATE_VENDOR_INVOICE          = "CreateVendorInvoice";
    const GET_VENDOR_PORTAL_INVITE_TOKEN = "GetVendorPortalInviteToken";
    const GET_VENDOR_PREFERENCES         = "GetVendorPreferences";
    const UPDATE_VENDOR_PREFERENCES      = "UpdateVendorPreferences";

    const BASE_PATH                  = "/twirp/vendorportal.Vendorportal/";

    const CONTENT_TYPE               = 'Content-Type';
    const X_APP_MODE                 = 'X-App-Mode';
    const X_RAZORPAY_TASKID_HEADER   = 'X-Razorpay-TaskId';
    const X_REQUEST_ID               = 'X-Request-ID';
    const X_MERCHANT_ID              = 'X-Merchant-Id';
    const X_USER_ID                  = 'X-User-Id';
    const X_ORG_ID                   = 'X-Org-Id';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        // We are using the same cred as of vendor payments to access the apis
        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo = $app['repo'];
    }

    public function listVendorInvoices(UserEntity $user, array $input, string $vendorInviteId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_VENDOR_INVOICES);

        $input['vendor_portal_invite_id'] = $vendorInviteId;

        $input['vendor_user_id'] = $user->getPublicId();

        return $this->makeRequest($url, $input);
    }

    public function getVendorInvoiceById(UserEntity $user, string $vendorPaymentId, string $vendorInviteId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_INVOICE);

        $input = [
            'vendor_payment_id'       => $vendorPaymentId,
            'vendor_portal_invite_id' => $vendorInviteId,
            'vendor_user_id'          => $user->getPublicId(),
        ];

        return $this->makeRequest($url, $input);
    }

    public function listTdsCategories()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_TDS_CATEGORIES);

        return $this->makeRequest($url, ['timestamp' => now()]);
    }

    public function getInvoiceSignedUrl(UserEntity $user, string $vendorInviteId, string $vendorPaymentId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_INVOICE_SIGNED_URL);

        $input = [
            'vendor_payment_id'       => $vendorPaymentId,
            'vendor_portal_invite_id' => $vendorInviteId,
            'vendor_user_id'          => $user->getPublicId(),
        ];

        return $this->makeRequest($url, $input);
    }

    public function listVendorPortalInvites(UserEntity $user)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_VENDOR_PORTAL_INVITES);

        $input = [
            'vendor_user_id' => $user->getPublicId(),
        ];

        return $this->makeRequest($url, $input);
    }

    public function createInvite(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_INVITE);

        $input['merchant_id'] = $merchant->getId();

        return $this->makeRequest($url, $input);
    }

    public function create(UserEntity $user, array $input, string $vendorInviteId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_VENDOR_INVOICE);

        $input['vendor_user_id'] = $user->getPublicId();

        $input['vendor_portal_invite_id'] = $vendorInviteId;

        return $this->makeRequest($url, $input);
    }

    public function acceptInvite(array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ACCEPT_INVITE);

        return $this->makeRequest($url, $input);
    }

    public function uploadInvoice(string $vendorInviteId, array $input, UserEntity $user)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPLOAD_INVOICE);

        $input['vendor_portal_invite_id'] = $vendorInviteId;

        $input['vendor_user_id'] = $user->getPublicId();

        $input['file'] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

        $input['file_name'] = $_FILES['file']['name'];

        return $this->makeRequest($url, $input);
    }

    public function getOcrData(string $vendorInviteId, string $ocrId, UserEntity $user)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_OCR_DATA);

        $input = [
            'ocr_reference_id'        => $ocrId,
            'vendor_portal_invite_id' => $vendorInviteId,
            'vendor_user_id'          => $user->getPublicId(),
        ];

        return $this->makeRequest($url, $input);
    }

    public function getInviteToken(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_PORTAL_INVITE_TOKEN);
        $input['merchant_id'] = $merchant->getId();

        return $this->makeRequest($url, $input);
    }

    public function getVendorPreferences(UserEntity $user, string $vendorInviteId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_PREFERENCES);

        $input['vendor_portal_invite_id'] = $vendorInviteId;

        $input['vendor_user_id'] = $user->getPublicId();

        return $this->makeRequest($url, $input);
    }

    public function updateVendorPreferences(UserEntity $user, array $input, string $vendorInviteId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_VENDOR_PREFERENCES);

        $input['vendor_portal_invite_id'] = $vendorInviteId;

        $input['vendor_user_id'] = $user->getPublicId();

        return $this->makeRequest($url, $input);
    }

    /**
     * @param string      $url
     * @param array       $data
     * @param array       $headers
     * @param string      $method
     * @param string|null $mode
     *
     * @return mixed
     *
     * @throws BadRequestException
     */
    protected function makeRequest(string $url    = '',
                                   array $data    = [],
                                   array $headers = [],
                                   string $method = 'POST',
                                   string $mode   = null)
    {
        $headers[self::CONTENT_TYPE] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID_HEADER] = $this->app['request']->getTaskId();

        $headers[self::X_REQUEST_ID] = $this->app['request']->getId();

        $headers[self::X_MERCHANT_ID] = $this->app['basicauth']->getMerchantId();

        $headers[self::X_USER_ID] = optional($this->app['basicauth']->getUser())->getId() ?? '';

        $headers[self::X_ORG_ID] = $this->app['basicauth']->getOrgId();

        if ($mode == null)
        {
            $headers[self::X_APP_MODE] = $this->app['rzp.mode'] ? $this->app['rzp.mode'] : Mode::LIVE;
        }
        else
        {
            $headers[self::X_APP_MODE] = $mode;
        }

        $options = [
            'auth' => ['api', $this->config['secret']],
            'timeout' => $this->config['timeout']
        ];

        $this->trace->info(TraceCode::VENDOR_PORTAL_REQUEST,
            [
                'url' => $url,
            ]);

        $response = Requests::request(
            $url,
            $headers,
            json_encode($data),
            $method,
            $options);

        $responseBody = json_decode($response->body, true);

        if ($response->status_code !== StatusCode::SUCCESS)
        {
            if ($responseBody !== null)
            {
                $description = array_pull($responseBody, 'msg', $responseBody);
            }
            else
            {
                $description = 'received empty response';
            }

            if ($response->status_code >= StatusCode::SERVER_ERROR)
            {
                throw new ServerErrorException($description, ErrorCode::SERVER_ERROR);
            }

            throw new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
                null,
                $description,
                $description);
        }

        return $responseBody;
    }
}
