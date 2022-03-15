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

    public function getTdsCategories()
    {
            return $this->service->getTdsCategories($this->ba->getMerchant());
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
        return $this->service->edit($this->ba->getMerchant(),$vendorPaymentId, $this->input);
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
}
