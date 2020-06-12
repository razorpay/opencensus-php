<?php

namespace RZP\Services;

use Requests;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\User\Entity;
use RZP\Http\Response\StatusCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * This class will be the main file that will talk to
 * Vendor Payment Micro Service and relay all the responses.
 * This will be as dummy as possible, and will only do conversions
 * Between the Restful API calls and the RPC API calls that the MS understands
 */
class VendorPayment
{
    const LIST_CONTACTS             = 'SearchContacts';
    const LIST_VENDOR_PAYMENTS      = 'ListVendorPayments';
    const PUSH_PAYOUT_STATUS_UPDATE = 'PayoutStatusChange';
    const GET_VENDOR_PAYMENT        = 'GetVendorPayment';
    const EXECUTE_VENDOR_PAYMENT    = 'ExecuteVendorPayment';
    const CREATE_CONTACT            = 'CreateContact';
    const UPDATE_CONTACT            = 'UpdateContactById';
    const GET_CONTACT               = 'GetContactById';
    const CREATE_VENDOR_PAYMENT     = 'CreateVendorPayment';
    const CONTACT_ID                = 'contact_id';
    const ID                        = 'id';
    const UPLOAD_INVOICE            = 'UploadInvoice';
    const GET_TDS_CATEGORIES        = 'GetTdsCategory';
    const EDIT_VENDOR_PAYMENTS      = 'EditVendorPayment';
    const CANCEL_VENDOR_PAYMENTS    = 'CancelVendorPayment';
    const GET_INVOICE_SIGNED_URL    = 'GetInvoiceSignedURL';
    const VP_SUMMARY_API            = 'SummaryApi';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo =  $app['repo'];
    }

    public function compositeExpandsHelper(array $input, MerchantEntity $merchant)
    {
        $result = [
        ];

        $this->expandUsers($result, $input);

        $this->expandContact($result, $input, $merchant);

        $this->expandFundAccount($result, $input, $merchant);

        $this->expandPayouts($result, $input, $merchant);

        $result['merchant'] = $merchant->toArrayPublic();

        return $result;
    }

    protected function expandContact(array &$result, array $input, MerchantEntity $merchant)
    {
        $contactId = array_pull($input, 'contact_id', null);

        if ($contactId === null)
        {
            return;
        }

        $contact = $this->repo->contact->findByPublicIdAndMerchant($contactId, $merchant);

        if (empty($contact) === false)
        {
            $result['contacts'][$contact->getPublicId()] = $contact->toArrayPublic();
        }
    }

    protected function expandFundAccount(array &$result, array $input, MerchantEntity $merchant)
    {
        $fundAccountId = array_pull($input, 'fund_account_id', null);

        if ($fundAccountId === null)
        {
            return;
        }

        $fundAccount = $this->repo
                            ->fund_account
                            ->findByPublicIdAndMerchant($fundAccountId, $merchant, ['expand' => ['contact']]);

        if (empty($fundAccount) === false)
        {
            $contact = $fundAccount->contact;

            $result['contacts'][$contact->getPublicId()] = $contact->toArrayPublic();

            $result['fund_accounts'][$fundAccount->getPublicId()] = $fundAccount->toArrayPublic();
        }
    }

    protected function expandPayouts(array &$result, array $input, MerchantEntity $merchant)
    {
        $payoutIds = array_pull($input, 'payout_ids', []);

        if (count($payoutIds) === 0)
        {
            return;
        }
        $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($payoutIds,
                                                                       $merchant,
                                                                       ['expand' => ['fund_account.contact']]);
        foreach ($payouts as $payout)
        {
            $fa = $payout->fundAccount;

            $contact = $fa->contact;

            $result['fund_accounts'][$fa->getPublicId()] = $fa->toArrayPublic();

            $result['contacts'][$contact->getPublicId()] = $contact->toArrayPublic();
        }

        $result['payouts'] = $payouts->toArrayPublic();

    }

    protected function expandUsers(array &$result, array $input)
    {
        $userIds = array_pull($input, 'user_ids', null);

        if ($userIds === null)
        {
            return;
        }

        $result['users'] = $this->repo->user->findManyByPublicIds($userIds)->toArrayPublic();
    }

    public function create(MerchantEntity $merchant, array $input, Entity $user = null)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::CREATE_VENDOR_PAYMENT);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getContactById(MerchantEntity $merchant, string $contactId)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::GET_CONTACT);

        $input = [self::CONTACT_ID => $contactId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function createContact(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::CREATE_CONTACT);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function updateContact(MerchantEntity $merchant, array $input, string $contactId)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::UPDATE_CONTACT);

        $input[self::CONTACT_ID] = $contactId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listContacts(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::LIST_CONTACTS);

        if (key_exists(self::ID, $input) === true)
        {
            $input[self::CONTACT_ID] = $input[self::ID];

            unset($input[self::ID]);
        }

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listVendorPayments(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::LIST_VENDOR_PAYMENTS);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getTdsCategories(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::GET_TDS_CATEGORIES);

        return $this->makeRequest($merchant, $url);
    }

    /**
     * This is being called from Payout Source Updater
     * @param PayoutEntity $payout
     * @return mixed
     * @throws BadRequestException
     */
    public function pushPayoutStatusUpdate(PayoutEntity $payout)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::PUSH_PAYOUT_STATUS_UPDATE);

        $input = [
            'payout_status' => $payout->getStatus(),
            'payout_id' => $payout->getPublicId(),
        ];

        return $this->makeRequest($payout->merchant, $url, $input);
    }

    public function getVendorPaymentById(MerchantEntity $merchant, string $vendorPaymentId)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::GET_VENDOR_PAYMENT);

        $input = ['id' => $vendorPaymentId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function execute(MerchantEntity $merchant,
                            string $vendorPaymentId,
                            array $input,
                            Entity $user = null)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::EXECUTE_VENDOR_PAYMENT);

        $input['id'] = $vendorPaymentId;

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function uploadInvoice(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::UPLOAD_INVOICE);
        // The MS we are calling, expects JSON content,
        // so we are sending the contents of the file in
        // base_64 encoded byte array
        $input = [
            'file'      => base64_encode(file_get_contents($_FILES['file']['tmp_name'])),
            'file_name' => $_FILES['file']['name']
        ];

        return $this->makeRequest($merchant, $url, $input);

    }
    public function edit(MerchantEntity $merchant,
                            string $vendorPaymentId,
                            array $input)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::EDIT_VENDOR_PAYMENTS);

        $input['id'] = $vendorPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function cancel(MerchantEntity $merchant,
                           string $vendorPaymentId,
                           array $input,
                           Entity $user = null)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::CANCEL_VENDOR_PAYMENTS);

        $input['id'] = $vendorPaymentId;

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['cancelling_user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getInvoiceSignedUrl(MerchantEntity $merchant,
                                        string $fileId)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::GET_INVOICE_SIGNED_URL);

        $input = ['file_id' => $fileId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function summary(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s', $this->config['url'], self::VP_SUMMARY_API);

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

        $this->trace->info(TraceCode::VENDOR_PAYMENT_REQUEST,
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

        $this->trace->info(TraceCode::VENDOR_PAYMENT_RESPONSE,
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
