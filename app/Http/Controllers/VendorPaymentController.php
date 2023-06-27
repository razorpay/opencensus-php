<?php

namespace RZP\Http\Controllers;

use Mail;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Mail\VendorPayments\Unpaid;
use RZP\Models\User\Core as UserCore;

class VendorPaymentController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['vendor-payment'];
    }

    public function list()
    {
        return $this->service->listVendorPayments($this->ba->getMerchant(), $this->input);
    }

    /**
     * Called by Vendor Payout Micro Service, for OTP verification
     * We dont have a Service Layer for this, so the business logic will reside here itself
     * We need here
     * otp
     * user_id
     * token
     * returns [success => true/false]
     */
    public function verifyOtp()
    {
        $input = $this->input;

        if (key_exists('user_id', $input) !== true)
        {
            return ApiResponse::json('User Id Required');
        }

        $userId = array_pull($input, 'user_id');

        $user = $this->repo->user->findByPublicId($userId);

        try
        {
            $response = (new UserCore())->verifyOtp($input,
                                                    $this->ba->getMerchant(),
                                                    $user,
                                                    false);

            if((isset($response['success']) === false) or
               ($response['success'] !== true))
            {
                $success = false;
            }
            else
            {
                $success = true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::OTP_VERIFICATION_FAILED_VENDOR_PAYOUT,
                               [
                                   'user_id' => $userId,
                                   'action'  => $input['action'],
                               ]);
            $success = false;
        }

        return ApiResponse::json(['success' => $success]);
    }

    /**
     * This will be called by VP-MS internally, for helping with expanding the following
     * fund_account_id
     * contact_id
     * merchant_id
     * user_ids
     * payout_ids
     *
     */
    public function compositeExpandsHelper()
    {
        return $this->service->compositeExpandsHelper($this->input);
    }

    public function executeVendorPaymentBulk()
    {
        return $this->service->executeVendorPaymentBulk($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function sendUpcomingMailCron()
    {
        return $this->service->sendUpcomingMailCron();
    }

    /**
     * Will be called by VendorPayment MS, to send email to the merchant,
     * when VP goes to UNPAID state
     * Todo: remove this when VP has a mailer integration
     *
     */
    public function internalSendFailureEmail()
    {
        $unpaidEmail = new Unpaid($this->input);

        Mail::queue($unpaidEmail);

        return ApiResponse::json(['success' => true]);
    }

    public function createVendorAdvance()
    {
        return $this->service->createVendorAdvance($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function getVendorAdvance(string $vendorAdvanceId)
    {
        return $this->service->getVendorAdvance($this->ba->getMerchant(), $vendorAdvanceId);
    }

    public function listVendorAdvances()
    {
        return $this->service->listVendorAdvances($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function sendMailGeneric()
    {
        return $this->service->sendMail($this->input);
    }

    public function getInvoiceSignedUrl(string $vendorPaymentId)
    {
        return $this->service->getInvoiceSignedUrl($this->ba->getMerchant(), $vendorPaymentId);
    }

    public function summary()
    {
        return $this->service->summary($this->ba->getMerchant(), $this->input);
    }

    public function uploadInvoice()
    {
        return $this->service->uploadInvoice($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function executeVendorPayment(string $vendorPaymentId)
    {
        return $this->service->execute($this->ba->getMerchant(), $vendorPaymentId, $this->input, $this->ba->getUser());
    }

    public function executeVendorPayment2fa(string $vendorPaymentId)
    {
        return $this->service->executeVendorPayment2fa($this->ba->getMerchant(), $vendorPaymentId, $this->input, $this->ba->getUser());
    }

    public function vendorSettlementSingle()
    {
        return $this->service->vendorSettlementSingle($this->ba->getMerchant(),$this->input, $this->ba->getUser());
    }

    public function vendorSettlementMultiple()
    {
        return $this->service->vendorSettlementMultiple($this->ba->getMerchant(),$this->input, $this->ba->getUser());
    }

    public function vendorSettlementMarkAsPaid()
    {
        return $this->service->vendorSettlementMarkAsPaid($this->ba->getMerchant(),$this->input, $this->ba->getUser());
    }

    public function getFundAccounts(string $contactId)
    {
        return $this->service->getFundAccounts($this->ba->getMerchant(),$this->input, $this->ba->getUser(), $contactId);
    }

    public function getVendorBalance(string $contactId)
    {
        return $this->service->getVendorBalance($this->ba->getMerchant(),$this->input, $this->ba->getUser(), $contactId);
    }

    public function getTdsCategories()
    {
            return $this->service->getTdsCategories($this->ba->getMerchant(), $this->input);
    }

    public function get(string $vendorPaymentId)
    {
        return $this->service->getVendorPaymentById($this->ba->getMerchant(), $vendorPaymentId);
    }

    public function create()
    {
        return $this->service->create($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function edit(string $vendorPaymentId)
    {
        return $this->service->edit($this->ba->getMerchant(),$vendorPaymentId, $this->input, $this->ba->getUser());
    }

    public function cancel(string $vendorPaymentId)
    {
        return $this->service->cancel($this->ba->getMerchant(), $vendorPaymentId, $this->input, $this->ba->getUser());
    }

    public function bulkCancel()
    {
        return $this->service->bulkCancel($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function accept(string $vendorPaymentId)
    {
        return $this->service->accept($this->ba->getMerchant(), $vendorPaymentId);
    }

    public function listContacts()
    {
        return $this->service->listContacts($this->ba->getMerchant(), $this->input);
    }

    public function getContact($contactId)
    {
        return $this->service->getContactById($this->ba->getMerchant(), $contactId);
    }

    public function createContact()
    {
        return $this->service->createContact($this->ba->getMerchant(), $this->input);
    }

    public function updateContact($contactId)
    {
        return $this->service->updateContact($this->ba->getMerchant(), $this->input, $contactId);
    }

    public function getOcrData(string $ocrReferenceId)
    {
        return $this->service->getOcrData($this->ba->getMerchant(), $ocrReferenceId);
    }

    public function ocrAccuracyCheck()
    {
        return $this->service->ocrAccuracyCheck();
    }

    public function markAsPaid()
    {
        return $this->service->markAsPaid($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function getReportingInfo()
    {
        return $this->service->getReportingInfo($this->ba->getMerchant(), $this->input);
    }

    public function bulkInvoiceDownload()
    {
        return $this->service->bulkInvoiceDownload($this->ba->getMerchant(), $this->input);
    }

    public function updateInvoiceFileId(string $vendorPaymentId)
    {
        return $this->service->updateInvoiceFileId($vendorPaymentId, $this->ba->getMerchant(), $this->input);
    }

    public function getInvoicesFromUfh(string $fileId)
    {
        return $this->service->getInvoicesFromUfh($this->ba->getMerchant(), $fileId);
    }

    public function getQuickFilterAmounts()
    {
        return $this->service->getQuickFilterAmounts($this->ba->getMerchant());
    }

    public function processIncomingMail()
    {
        $response = $this->service->processIncomingMail($this->input);

        $code = 400;

        if (isset($response['status_code']))
        {
            $code = $response[ 'status_code' ];
        }

        if ($code != 200)
        {
            $responseBody['error'] = $response['body'];
        }
        else
        {
            $responseBody = $response['body'];
        }

        $response = ApiResponse::json($responseBody, $code);

        return $response;
    }

    public function getMerchantEmailAddress()
    {
        return $this->service->getMerchantEmailAddress($this->ba->getMerchant());
    }

    public function createMerchantEmailMapping()
    {
        return $this->service->createMerchantEmailMapping($this->ba->getMerchant());
    }

    public function getAutoProcessedInvoice(string $fileId)
    {
        return $this->service->getAutoProcessedInvoice($this->ba->getMerchant(), $fileId);
    }

    public function inviteVendor()
    {
        return $this->service->inviteVendor($this->ba->getMerchant(), $this->input);
    }

    public function disableVendorPortal(string $contactId)
    {
        return $this->service->disableVendorPortal($this->ba->getMerchant(), $contactId);
    }

    public function enableVendorPortal(string $contactId)
    {
        return $this->service->enableVendorPortal($this->ba->getMerchant(), $contactId);
    }

    public function listVendors()
    {
        return $this->service->listVendors($this->ba->getMerchant(), $this->input);
    }

    public function createBusinessInfo()
    {
        return $this->service->createBusinessInfo($this->ba->getMerchant(), $this->input);
    }

    public function getBusinessInfoStatus()
    {
        return $this->service->getBusinessInfoStatus($this->ba->getMerchant());
    }

    public function checkIfInvoiceExistForVendor()
    {
        return $this->service->checkIfInvoiceExistForVendor($this->ba->getMerchant(), $this->input);
    }

    public function createFileUpload()
    {
        return $this->service->createFileUpload($this->ba->getMerchant(), $this->input);
    }

    public function getFileUpload()
    {
        return $this->service->getFileUpload($this->ba->getMerchant(), $this->input);
    }

    public function deleteFileUpload(string $ufhFileId)
    {
        return $this->service->deleteFileUpload($this->ba->getMerchant(), $ufhFileId);
    }

    public function addOrUpdateSettings()
    {
        return $this->service->addOrUpdateSettings($this->ba->getMerchant(), $this->input);
    }

    public function getSettings()
    {
        return $this->service->getSettings($this->ba->getMerchant(), $this->input);
    }

    public function approveReject()
    {
        $response = ApiResponse::json([]);

        try {

            $response = ApiResponse::json($this->service->approveReject($this->input), 200);

        } catch (\Throwable $e)
        {
            $response = ApiResponse::json("Error", 400);
        }

        $response->headers->set('Access-Control-Allow-Origin', $this->config['applications.vendor_payments.public_approve_reject_url']);

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS' );

        return $response ;
    }


    public function getLatestApprovers()
    {
        return $this->service->getLatestApprovers($this->ba->getMerchant(), $this->input);
    }

    public function getTimelineView()
    {
        return $this->service->getTimelineView($this->ba->getMerchant(), $this->input);
    }

    public function allowCorsForPublicApproveRejectPage()
    {
        $response = ApiResponse::json([]);

        $this->addCorsHeaders($response);

        return $response;
    }

    private function addCorsHeaders(& $response)
    {
        $response->headers->set('Access-Control-Allow-Origin', $this->config['applications.vendor_payments.public_approve_reject_url']);

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS' );
    }
}
