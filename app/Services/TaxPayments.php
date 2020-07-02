<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Response\StatusCode;
use RZP\Exception\BadRequestException;
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
    const BASE_PATH              = 'twirp/razorpay.vendorpayments.taxpayments.Taxpayments';
    const GET_ALL_SETTINGS       = 'GetAllSettings';
    const ADD_OR_UPDATE_SETTINGS = 'AddOrUpdateSettings';
    const GET_TAX_PAYMENT_BY_ID  = 'GetTaxPayment';
    const LIST_TAX_PAYMENTS      = 'ListTaxPayments';


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

        $this->repo =  $app['repo'];

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

    protected function makeRequest(MerchantEntity $merchant,
                                   string $url,
                                   array $data = [],
                                   array $headers = [],
                                   string $method = 'POST')
    {
        $data = array_merge($data, ['merchant_id' => $merchant->getId()]);

        $headers['Content-Type'] = 'application/json';

        $headers['X-Task-ID'] = $this->app['request']->getId();

        $options = ['auth' => ['api', $this->config['secret']]];

        $dataLogged = $data;

        unset($dataLogged['file']);

        $this->trace->info(TraceCode::TAX_PAYMENT_REQUEST,
                           [
                               'headers' => $headers,
                               'url' => $url,
                               'data' => $dataLogged,
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
