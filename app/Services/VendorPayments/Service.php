<?php

namespace RZP\Services\VendorPayments;

use Mail;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\User\Entity;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Mail\VendorPayments\GenericVendorPaymentEmail;

/**
 * This class will be the main file that will talk to
 * Vendor Payment Micro Service and relay all the responses.
 * This will be as dummy as possible, and will only do conversions
 * Between the Restful API calls and the RPC API calls that the MS understands
 */
class Service
{
    const LIST_CONTACTS                 = 'SearchContacts';
    const LIST_VENDOR_PAYMENTS          = 'ListVendorPayments';
    const PUSH_PAYOUT_STATUS_UPDATE     = 'PayoutStatusChange';
    const GET_VENDOR_PAYMENT            = 'GetVendorPayment';
    const EXECUTE_VENDOR_PAYMENT        = 'ExecuteVendorPayment';
    const EXECUTE_VENDOR_PAYMENT_BULK   = 'ExecuteVendorPaymentBulk';
    const CREATE_CONTACT                = 'CreateContact';
    const UPDATE_CONTACT                = 'UpdateContactById';
    const GET_CONTACT                   = 'GetContactById';
    const CREATE_VENDOR_PAYMENT         = 'CreateVendorPayment';
    const CONTACT_ID                    = 'contact_id';
    const ID                            = 'id';
    const UPLOAD_INVOICE                = 'UploadInvoice';
    const GET_TDS_CATEGORIES            = 'GetTdsCategory';
    const UPCOMING_MAIL_CRON            = 'UpcomingMailCron';
    const EDIT_VENDOR_PAYMENTS          = 'EditVendorPayment';
    const CANCEL_VENDOR_PAYMENTS        = 'CancelVendorPayment';
    const BULK_CANCEL_VENDOR_PAYMENTS   = 'BulkCancelVP';
    const ACCEPT_VENDOR_PAYMENTS        = 'AcceptVendorPayment';
    const GET_INVOICE_SIGNED_URL        = 'GetInvoiceSignedURL';
    const VP_SUMMARY_API                = 'SummaryApi';
    const GET_OCR_DATA                  = 'GetOcrData';
    const OCR_ACCURACY_CHECK            = 'GetOcrAccuracy';
    const MARK_AS_PAID                  = 'MarkAsPaid';
    const UFH_BULK_DOWNLOAD             = 'InitiateBulkInvoiceDownload';
    const UPDATE_INVOICE_FILE_ID        = 'UpdateInvoiceFileId';
    const GET_INVOICES_FROM_UFH         = 'GetUfhFile';
    const GET_VENDOR_BY_CONTACT_ID      = 'GetVendorByContactId';
    const CREATE_VENDOR                 = 'CreateVendor';
    const UPDATE_VENDOR                 = 'UpdateVendor';
    const GET_VENDOR_BULK               = 'GetVendorBulk';
    const GET_QUICK_FILTER_AMOUNTS      = 'GetQuickFilterAmounts';
    const RECEIVE_EMAIL                 = 'ReceiveVPEmailMessage';
    const GET_MERCHANT_EMAIL_ADDRESS    = 'GetMerchantEmailAddress';
    const FUND_ACCOUNT_LINKING          = 'FundAccountCreated';
    const CREATE_MERCHANT_EMAIL_MAPPING = 'CreateMerchantEmailMapping';
    const GET_AUTO_PROCESSED_INVOICE    = 'GetAutoProcessedInvoiceDetails';
    const TRIGGER_VENDOR_INVITE         = 'TriggerEiVendorInvitationEmail';

    const BASE_PATH = 'twirp/vendorpayments.Vendorpayments';

    const DATA               = 'data';
    const TEMPLATE_NAME      = 'template_name';
    const SUBJECT            = 'subject';
    const NAME               = 'name';
    const TO_EMAIL           = 'to_emails';
    const GET_REPORTING_INFO = 'GetReportingInfo';
    const CONTENT_TYPE       = 'Content-Type';
    const X_TASK_ID          = 'X-Task-ID';
    const X_APP_MODE         = 'X-App-Mode';

    const MESSAGE_ID  = 'message_id';
    const RECIPIENT   = 'recipient';
    const SENDER      = 'sender';
    const SIGNATURE   = 'signature';
    const TOKEN       = 'token';
    const TIMESTAMP   = 'timestamp';
    const FILE        = 'file';
    const FILE_TYPE   = 'type';
    const FILE_FORMAT = 'format';
    const FILE_SIZE   = 'size';
    const ATTACHMENTS = 'attachments';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo = $app['repo'];
    }

    public function sendMail(array $input)
    {
        (new Validator())->validateInput(Validator::SEND_MAIL, $input);

        Mail::queue(new GenericVendorPaymentEmail($input[self::TO_EMAIL],
                                                  $input[self::SUBJECT],
                                                  $input[self::TEMPLATE_NAME],
                                                  $input[self::DATA]));

        return ['success' => true];
    }

    public function compositeExpandsHelper(array $input)
    {
        $result = [
        ];

        $this->expandUsers($result, $input);

        $this->expandContacts($result, $input);

        $this->expandFundAccounts($result, $input);

        $this->expandPayouts($result, $input);

        $this->expandMerchants($result, $input);

        return $result;
    }

    protected function expandContacts(array &$result, array $input)
    {
        $contactId = array_pull($input, 'contact_ids', null);

        if ($contactId === null)
        {
            return;
        }

        $contacts = $this->repo->contact->findManyByPublicIds($contactId);

        foreach ($contacts as $contact)
        {
            $result['contacts'][$contact->getPublicId()] = $contact->toArrayPublic();
        }
    }

    protected function expandMerchants(array &$result, array $input)
    {
        $merchantIds = array_pull($input, 'merchant_ids', null);

        if ($merchantIds === null)
        {
            return;
        }

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIds);

        foreach ($merchants as $merchant)
        {
            $result['merchants'][$merchant->getPublicId()] = $merchant->toArrayPublic();
        }
    }


    protected function expandFundAccounts(array &$result, array $input)
    {
        $fundAccountIds = array_pull($input, 'fund_account_ids', null);

        if ($fundAccountIds === null)
        {
            return;
        }

        $fundAccounts = $this->repo
            ->fund_account
            ->findManyByPublicIds($fundAccountIds, ['expand' => ['contact']]);

        foreach ($fundAccounts as $fa)
        {
            $contact = $fa->contact;

            $result['contacts'][$contact->getPublicId()] = $contact->toArrayPublic();

            $result['fund_accounts'][$fa->getPublicId()] = $fa->toArrayPublic();
        }
    }

    protected function expandPayouts(array &$result, array $input)
    {
        $payoutIds = array_pull($input, 'payout_ids', []);

        if (count($payoutIds) === 0)
        {
            return;
        }

        $payouts = $this->repo->payout->findManyByPublicIds($payoutIds,
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
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_VENDOR_PAYMENT);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function executeVendorPaymentBulk(MerchantEntity $merchant, array $input, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EXECUTE_VENDOR_PAYMENT_BULK);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getContactById(MerchantEntity $merchant, string $contactId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_CONTACT);

        $input = [self::CONTACT_ID => $contactId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function createContact(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_CONTACT);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function updateContact(MerchantEntity $merchant, array $input, string $contactId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_CONTACT);

        $input[self::CONTACT_ID] = $contactId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listContacts(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_CONTACTS);

        if (key_exists(self::ID, $input) === true)
        {
            $input[self::CONTACT_ID] = $input[self::ID];

            unset($input[self::ID]);
        }

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listVendorPayments(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_VENDOR_PAYMENTS);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getTdsCategories(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TDS_CATEGORIES);

        return $this->makeRequest($merchant, $url);
    }

    public function sendUpcomingMailCron()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPCOMING_MAIL_CRON);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    /**
     * This is being called from Payout Source Updater
     * @param PayoutEntity $payout
     * @param string $mode
     * @return mixed
     * @throws BadRequestException
     */
    public function pushPayoutStatusUpdate(PayoutEntity $payout, string $mode)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::PUSH_PAYOUT_STATUS_UPDATE);

        $input = [
            'payout_status' => $payout->getStatus(),
            'payout_id'     => $payout->getPublicId(),
        ];

        return $this->makeRequest($payout->merchant, $url, $input, [], 'POST', $mode);
    }

    public function getVendorPaymentById(MerchantEntity $merchant, string $vendorPaymentId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_PAYMENT);

        $input = ['id' => $vendorPaymentId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function execute(MerchantEntity $merchant,
                            string $vendorPaymentId,
                            array $input,
                            Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EXECUTE_VENDOR_PAYMENT);

        $input['id'] = $vendorPaymentId;

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function uploadInvoice(MerchantEntity $merchant, array $input, Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPLOAD_INVOICE);
        // The MS we are calling, expects JSON content,
        // so we are sending the contents of the file in
        // base_64 encoded byte array

        $input['file'] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

        $input['file_name'] = $_FILES['file']['name'];

        if(!empty($user))
        {
            $input['user_id'] = $user->getPublicId();
        }

        return $this->makeRequest($merchant, $url, $input);

    }
    public function edit(MerchantEntity $merchant,
                            string $vendorPaymentId,
                            array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EDIT_VENDOR_PAYMENTS);

        $input['id'] = $vendorPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function cancel(MerchantEntity $merchant,
                           string $vendorPaymentId,
                           array $input,
                           Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CANCEL_VENDOR_PAYMENTS);

        $input['id'] = $vendorPaymentId;

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['cancelling_user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function accept(MerchantEntity $merchant, string $vendorPaymentId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ACCEPT_VENDOR_PAYMENTS);

        $input = ['id' => $vendorPaymentId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getInvoiceSignedUrl(MerchantEntity $merchant,
                                        string $fileId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_INVOICE_SIGNED_URL);

        $input = ['file_id' => $fileId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function summary(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::VP_SUMMARY_API);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function bulkCancel(MerchantEntity $merchant,
                           array $input,
                           Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::BULK_CANCEL_VENDOR_PAYMENTS);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['cancelling_user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getOcrData(MerchantEntity $merchant,
                         string $ocrReferenceId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_OCR_DATA);

        $input = ['ocr_reference_id' => $ocrReferenceId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function ocrAccuracyCheck()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::OCR_ACCURACY_CHECK);

        return $this->makeRequest(null, $url, ['time' => now()]);
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

    public function getReportingInfo(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_REPORTING_INFO);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function bulkInvoiceDownload(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UFH_BULK_DOWNLOAD);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function updateInvoiceFileId(string $vendorPaymentId, MerchantEntity $merchant, array $input)
    {
        $input['vendor_payment_id'] = $vendorPaymentId;

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_INVOICE_FILE_ID);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getInvoicesFromUfh(MerchantEntity $merchant, string $fileId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_INVOICES_FROM_UFH);

        $input = ['file_id' => $fileId];

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getVendorByContactId(MerchantEntity $merchant, array $data)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_BY_CONTACT_ID);

        return $this->makeRequest($merchant, $url, $data);
    }

    public function createVendor(MerchantEntity $merchant, array $data)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_VENDOR);

        return $this->makeRequest($merchant, $url, $data);
    }

    public function updateVendor(MerchantEntity $merchant, array $data)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_VENDOR);

        return $this->makeRequest($merchant, $url, $data);
    }

    public function getVendorBulk(MerchantEntity $merchant, array $data)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_VENDOR_BULK);

        return $this->makeRequest($merchant, $url, $data);
    }

    public function getQuickFilterAmounts(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_QUICK_FILTER_AMOUNTS);

        return $this->makeRequest($merchant, $url);
    }

    public function processIncomingMail(array $input)
    {
        if (empty($_FILES === false))
        {
            $input[self::ATTACHMENTS] = array();
            foreach ($_FILES as $file)
            {
                $attachment[self::FILE]        = base64_encode(file_get_contents($file['tmp_name']));
                $attachment[self::FILE_FORMAT] = $file[self::FILE_TYPE];
                $attachment[self::FILE_SIZE]   = $file[self::FILE_SIZE];
                $attachment[self::NAME]        = $file[self::NAME];
                array_push($input['attachments'], $attachment);
            }
        }

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::RECEIVE_EMAIL);

        return $this->makeRequest(null, $url, $input, [], 'POST', Mode::LIVE);
    }

    public function getMerchantEmailAddress(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_MERCHANT_EMAIL_ADDRESS);

        return $this->makeRequest($merchant, $url);
    }

    public function pushFundAccountDetails(array $data, string $mode)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FUND_ACCOUNT_LINKING);

        return $this->makeRequest(null, $url, $data, [], 'POST', $mode);
    }

    public function createMerchantEmailMapping(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_MERCHANT_EMAIL_MAPPING);

        return $this->makeRequest($merchant, $url);
    }

    public function inviteVendor(MerchantEntity $merchant, array $data)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::TRIGGER_VENDOR_INVITE);

        return $this->makeRequest($merchant, $url, $data);
    }

    public function getAutoProcessedInvoice(MerchantEntity $merchant, string $fileId)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_AUTO_PROCESSED_INVOICE);

        $data = [
            'merchant_id'     => $merchant->getMerchantId(),
            'invoice_file_id' => $fileId
        ];

        return $this->makeRequest($merchant, $url, $data, [], 'POST');
    }

    protected function makeRequest(MerchantEntity $merchant = null,
                                   string $url = '',
                                   array $data = [],
                                   array $headers = [],
                                   string $method = 'POST',
                                   string $mode = null)
    {
        if ($merchant !== null)
        {
            $data = array_merge($data, ['merchant_id' => $merchant->getId()]);
        }

        $headers[self::CONTENT_TYPE] = 'application/json';

        $headers[self::X_TASK_ID] = $this->app['request']->getId();

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

        $this->trace->info(TraceCode::VENDOR_PAYMENT_REQUEST,
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
            $description = '';

            if ($responseBody !== null)
            {
                $description = array_pull($responseBody, 'msg', $responseBody);
            }
            else
            {
                $description = 'received empty response';
            }

            throw new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
                null,
                $description,
                $description);
        }
        return $responseBody;
    }
}
