<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Entity;
use RZP\Models\Settings\Module;
use RZP\Models\Settings\Service;
use RZP\Http\Response\StatusCode;
use RZP\Models\Settings\Accessor;
use RZP\Exception\BadRequestException;
use RZP\Models\Settings\GlobalAccessor;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class TaxPayments
 * @package RZP\Services
 * This class is responsible to talk to the TaxPayments APIs that are hosted
 * on the VendorPayments APP
 */
class TaxPayments
{
    const BASE_PATH                = 'twirp/razorpay.vendorpayments.taxpayments.Taxpayments';
    const GET_ALL_SETTINGS         = 'GetAllSettings';
    const ADD_OR_UPDATE_SETTINGS   = 'AddOrUpdateSettings';
    const GET_TAX_PAYMENT_BY_ID    = 'GetTaxPayment';
    const LIST_TAX_PAYMENTS        = 'ListTaxPayments';
    const PAY_TAX_PAYMENTS         = 'PayTaxPayment';
    const BULK_PAY_TAX_PAYMENTS    = 'BulkPayTaxPayments';
    const INITIATE_MONTHLY_PAYOUTS = 'InitiateMonthlyPayouts';
    const TAX_PAYMENT_ENABLED_KEY  = 'tax_payment_enabled';
    const MARK_AS_PAID             = 'MarkAsPaid';
    const UPLOAD_CHALLAN           = 'UploadChallan';
    const EDIT_TP                  = 'EditTp';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        // we are using the same creds as that of vendor-payments to access the APIs
        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo = $app['repo'];
    }

    /**
     * This will query the settings service and get all the merchants that have the tax-payment settings enabled
     */
    public function settingsOfTaxPaymentEnabledMerchants()
    {
         $settings = (new Service())->getSettingsIfKeyPresent(Module::TAX_PAYMENTS, self::TAX_PAYMENT_ENABLED_KEY);

        $settingsOfEnabledMerchants = [];

        foreach ($settings as $setting)
        {
            if (boolval($setting['value']) === true)
            {
                $merchant = $this->repo->merchant->find($setting['entity_id']);

                $settingsAccessor = Accessor::for($merchant, Module::TAX_PAYMENTS);

                array_push($settingsOfEnabledMerchants,
                           [
                               'merchant_id' => $merchant->getId(),
                               'settings'    => $settingsAccessor->all()->toArray()
                           ]);
            }
        }
        return $settingsOfEnabledMerchants;
    }

    public function initiateMonthlyPayouts()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INITIATE_MONTHLY_PAYOUTS);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    public function payTaxPayment(MerchantEntity $merchant, string $taxPaymentId, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                                          null,
                                          [
                                              'tax_payment_id' => $taxPaymentId,
                                              'merchant_id'    => $merchant->getPublicId()
                                          ]);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::PAY_TAX_PAYMENTS);

        $input['tax_payment_id'] = $taxPaymentId;

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function bulkPayTaxPayment(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user == null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::BULK_PAY_TAX_PAYMENTS);

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getAllSettings(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_ALL_SETTINGS);

        return $this->makeRequest($merchant, $url);
    }

    public function addOrUpdateSettings(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADD_OR_UPDATE_SETTINGS);

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listTaxPayments(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_TAX_PAYMENTS);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getTaxPayment(MerchantEntity $merchant, string $taxPaymentId, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TAX_PAYMENT_BY_ID);

        $input['tax_payment_id'] = $taxPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function markAsPaid(MerchantEntity $merchant,
                               array $input,
                               Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::MARK_AS_PAID);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['manually_paid_user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function uploadChallan(MerchantEntity $merchant,array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPLOAD_CHALLAN);
        // The MS we are calling, expects JSON content,
        // so we are sending the contents of the file in
        // base_64 encoded byte array

        $input['file'] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));
        $input['file_name'] = $_FILES['file']['name'];


        return $this->makeRequest($merchant, $url, $input);

    }

    public function edit(MerchantEntity $merchant,
                         string $taxPaymentId,
                         array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EDIT_TP);

        $input['tax_payment_id'] = $taxPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    protected function makeRequest(MerchantEntity $merchant = null,
                                   string $url = '',
                                   array $data = [],
                                   array $headers = [],
                                   string $method = 'POST')
    {
        if ($merchant !== null)
        {
            $data = array_merge($data, ['merchant_id' => $merchant->getId()]);
        }

        $headers['Content-Type'] = 'application/json';

        $headers['X-Task-ID'] = $this->app['request']->getId();

        $options = ['auth' => ['api', $this->config['secret']]];

        $dataLogged = $data;

        unset($dataLogged['file']);

        $this->trace->info(TraceCode::TAX_PAYMENT_REQUEST,
                           [
                               'headers' => $headers,
                               'url'     => $url,
                               'data'    => $dataLogged,
                           ]);
        $response = Requests::$method(
            $url,
            $headers,
            json_encode($data),
            $options);

        $responseBody = json_decode($response->body, true);

        $this->trace->info(TraceCode::TAX_PAYMENT_RESPONSE,
                           [
                               'response' => $responseBody
                           ]);

        if ($response->status_code !== StatusCode::SUCCESS)
        {
            $description = array_pull($responseBody, 'msg', $responseBody);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
                                          null,
                                          $description,
                                          $description);
        }

        return $responseBody;
    }
}
