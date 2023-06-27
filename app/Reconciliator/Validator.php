<?php

namespace RZP\Reconciliator;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Reconciliator\RequestProcessor;

class Validator extends Base\Core
{
    const ACCEPTED_EXTENSIONS_MAP = [
        'csv'  => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'text/plain'],
        'txt'  => ['text/plain', 'application/octet-stream', 'audio/x-unknown', 'text/x-Algol68', 'text/x-algol68', 'text/csv'],
        // Ensure that this is always above 'xlsx' because of `getExtensionFromContentType`
        'zip'  => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'application/zip', 'application/octet-stream', 'application/vnd.ms-excel'],
        // `text/plain` is being added here because HDFC sends CSV files with XLS extension. kthxbye
        // `application/CDFV2-unknown` is being sent for FirstData files. sigh.
        'xls'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                   'application/vnd.ms-office', 'application/octet-stream', 'text/plain',
                   'application/cdfv2-unknown'],
        'xlsb' => [
            'application/excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-office',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip',
            'application/octet-stream', 'application/vnd.oasis.opendocument.spreadsheet',
        ],
        'gpg'  => ['application/octet-stream', 'application/pgp', 'application/pgp-encrypted'],
        'pgp'  => ['application/pgp', 'application/pgp-encrypted'],
        'rpt'  => ['text/plain', 'text/csv'],
        'dat'  => ['text/plain'],
        '7z'   => ['application/x-7z-compressed'],
        'iob'  => ['text/plain'],
    ];

    const GATEWAY_SUBJECT_REGEX = [
        RequestProcessor\Base::HDFC               => [
                                                        "/^'{0,1}Email MPR as of [0-9]{2}-"
                                                        . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/"
                                                     ],
        RequestProcessor\Base::KOTAK              => ["/^PG Transaction File/", "/^PG Online Refund/"],
        RequestProcessor\Base::OLAMONEY           => ["/^Merchant Settlement File/"],
        RequestProcessor\Base::FREECHARGE         => ["/^Merchant Settlement Report/"],
        RequestProcessor\Base::NETBANKING_AXIS    => [
                                                        "/^MIS file for (0[1-9]|[12][0-9]|3[01])[\/-](0[1-9]|1[0-2])[\/-]20[0-9]{2}, "
                                                        . "for all RazorPay & Payees : Payeespecific MIS\(FEBA\)/"
                                                     ],
        RequestProcessor\Base::NETBANKING_BOB     => [  "/^(RE: )?Razorpay_Scroll_ of /",
                                                        "/^Bank of Baroda RazorPay Internet Banking payment recon file for date "
                                                        . "\(20[0-9]{2}-[0-9]{2}-[0-9]{2}\)/"
                                                     ],
        RequestProcessor\Base::NETBANKING_CSB     => ["/^RAZORPAY_Recon File/"],
        RequestProcessor\Base::NETBANKING_SBI     => ["/(?i)^RAZORPAY Recon File/"],
        RequestProcessor\Base::NETBANKING_ICICI   => ["/Consumer Durable Loan booking Razorpay Reports for [0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_FEDERAL => [
                                                        "/^MIS Report File Dated "
                                                        . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}---razorpay/"
                                                     ],

        RequestProcessor\Base::NETBANKING_CORPORATION => [
                                                            "/^Corporation Bank - FEBA - RazorPay Recon File "
                                                            . "(0[1-9]|[12][0-9]|3[01])[\/-](0[1-9]|1[0-2])[\/-]20[0-9]{2}/"
                                                         ],
        RequestProcessor\Base::NETBANKING_SIB     => ["/^Daily Transaction Details/"],
        RequestProcessor\Base::NETBANKING_SCB     => ["/^Scb Reconciliation File/"],
        RequestProcessor\Base::AXIS               => [
                                                        "/^Axis Estatement [0-9]{2}-"
                                                        . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/"
                                                     ],
        RequestProcessor\Base::FIRST_DATA         => ["/Statement for Merchant MID No. razorpay/"],
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK  => ["/^RAZOR_VA_REPORT$/"],
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK=> ["/Confidential \| Cash Management MIS Report E-Collect/"],
        RequestProcessor\Base::HITACHI            => ["/RAZORPAY RBL SETTLED REPORT for the date of [0-9]{2}-"
                                                     . "[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::UPI_ICICI          => ["/Eazypay app sales summary-[0-9]{2}-[0-9]{2}-20[0-9]{2}_[0-9]{2}-[0-9]{2}-20[0-9]{2}/",
                                                         "/Eazypay app\s*sales summary-[0-9]{2}-"
                                                         . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/",
                                                         "/Refund MIS for [0-9]{6}_RAZORPAY/"
                                                     ],
        RequestProcessor\Base::PAYZAPP            => [
                                                         "/Razorpay_Software Payout Detailed Report GST "
                                                         . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) [0-9][0-9]?,\s*"
                                                         ."20[0-9]{2}/"
                                                     ],
        RequestProcessor\Base::AIRTEL             => ["/Ecom Merchant Transaction_Report for [0-9]+/"],
        RequestProcessor\Base::UPI_AXIS           => [  "/Razorpay Software Pvt Ltd UPI transactions - "
                                                        . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}/"],
        RequestProcessor\Base::UPI_HDFC           => [ "/Merchant Payout Report/"],
        RequestProcessor\Base::CARD_FSS_HDFC      => ["/^Settlement Report FSSPaY - Razorpay/"],
        RequestProcessor\Base::CARD_FSS_SBI       => ["/IPAY MIS File dated [0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::UPI_HULK           => ["/Razorpay_Transaction_Details_[0-9]{2}-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/"],
        RequestProcessor\Base::EMANDATE_AXIS      => ["/axis e[\-]?mandate debit file/i"],
        RequestProcessor\Base::NETBANKING_ALLAHABAD   => ["/Recon file for [0-9]{2}.[0-9]{2}.20[0-9]{2}/"],
        RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY => ["/^Flexmoney Recon and Refund files/"],
        RequestProcessor\Base::PHONEPE            => [".*/Settlement Report/"],
        RequestProcessor\Base::NETBANKING_KVB     => ["/Recon file [0-9]{2}.[0-9]{2}.20[0-9]{2}/"],
        RequestProcessor\Base::BAJAJFINSERV       => ["/Payment MIS_Razorpay Software_ [0-9]{1,2} (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) 20[0-9]{2}/"],
        RequestProcessor\Base::YES_BANK           => ["/Yes Bank_ MPR [0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::HDFC_DEBIT_EMI     => ["/^DCEMI Reconciliation & Payment Summary Report/"],
        RequestProcessor\Base::UPI_JUSPAY         => ["/BAJAJ TXN DETAILS/"],
        RequestProcessor\Base::NETBANKING_SVC     => ['/Recon file for the date [0-9]{2}.[0-9]{2}.20[0-9]{2} to [0-9]{2}.[0-9]{2}.20[0-9]{2}/'],
        RequestProcessor\Base::NETBANKING_JSB     => ["/Payment Gateway Reconcilation File from JFS/"],
        RequestProcessor\Base::NETBANKING_FSB     => ["/Recon file for transaction dated [0-9]{2}-(January|February|March|April|May|June|July|August|September|October|November|December)-20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_IOB     => ["/IOB RazorPay Recon File -20[0-9]{6}/"],
        RequestProcessor\Base::NETBANKING_JKB     => ["/Recon File of Razorpay Dated:[0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_DCB     => ["/RAZORPAY RECON file dt. [0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_DLB     => ["/RazorPay - Dhanalaxmi Bank PG Recon File New/"],
        RequestProcessor\Base::NETBANKING_RBL     => ["/RBL (Razorpay|CIB) PG Recon File/"],
        RequestProcessor\Base::CARDLESS_EMI_ZESTMONEY  => ["/Settlement_RazorpayPG_[0-9]{2}-[0-9]{2}-20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_BDBL    => ["/Razorpay Reconciliation Dt. [0-9]{2}.[0-9]{2}.20[0-9]{2}/"],
        RequestProcessor\Base::NETBANKING_SARASWAT    => ["/Saraswat Bank Recon File"],
        RequestProcessor\Base::NETBANKING_UCO     => [""],
        RequestProcessor\Base::EMERCHANTPAY       => ["/Settlement Razorpay Software Private Ltd (Trustly|Poli|Sofort|Giropay) (EUR|GBP|AUD) [0-9]{2}-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/"],
        RequestProcessor\Base::WALLET_BAJAJ       => ["/(?i)^RZP MID BFL0000001675590 Settlement Data(.+)?/"]
    ];

    const GATEWAY_BODY_REGEX = [
        RequestProcessor\Base::OLAMONEY               => ["/^Please find settlement report for /"],
        RequestProcessor\Base::FREECHARGE             => ["/Please view your settlement report/"],
        RequestProcessor\Base::NETBANKING_AXIS        => [
                                                            "/Kindly find attached below the MIS for "
                                                            . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}/"
                                                         ],
        RequestProcessor\Base::NETBANKING_SIB         => ["/^Please find attached the text file which contains the daily transaction details/"],
        RequestProcessor\Base::NETBANKING_ICICI       => ["/Please find below the payment report for the day./"],
        RequestProcessor\Base::NETBANKING_FEDERAL     => [
                                                            "/^MIS Report File Dated "
                                                            . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}/"
                                                         ],
        RequestProcessor\Base::AXIS                   => [
                                                            "/Please find attached the settlement file for today."
                                                            . " You net amount settled is/"
                                                         ],
        RequestProcessor\Base::NETBANKING_CSB         => ["/Please find attached, the recon file for the date/"],
        RequestProcessor\Base::NETBANKING_SBI         => ["/Please find the attached file/"],
        RequestProcessor\Base::FIRST_DATA             => ["/the statement of transactions for MID (.)*razorpay/"],
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK      => ["/Please find the hourly report of Virtual Accounts./"],
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK    => ["/Please find attached subject scheduled reports./"],
        RequestProcessor\Base::HITACHI                => ["/Please find the attached RAZORPAY RBL Settled Transaction report./"],
        RequestProcessor\Base::UPI_ICICI              => [
                                                            "/Please find attached the UPI Transaction Report MIS as on"
                                                            ."\s*[0-9]{2}-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/",
                                                            "/Please find attached the Refund Report as on/",
                                                            "/Please find attached the UPI Transaction Report MIS as on"
                                                            . "\s*[0-9]{2}-[0-9]{2}-20[0-9]{2}_[0-9]{2}-[0-9]{2}-20[0-9]{2}/",
                                                          ],
        RequestProcessor\Base::NETBANKING_CORPORATION  => ["/Please find attached RECON file for RazorPay/"],
        RequestProcessor\Base::PAYZAPP                 => [
                                                             "/Please find Merchant payout report attached for Date "
                                                             ."(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) [0-9][0-9]?,\s*"
                                                             ."20[0-9]{2}/"
                                                          ],
        RequestProcessor\Base::UPI_AXIS                => ["/The summary of transaction initiated from \"(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}\"/"],
        RequestProcessor\Base::UPI_HDFC                => ["/Please Find Attachment For Merchant Payout Report/"],
        RequestProcessor\Base::AIRTEL                  => ["/PFA your merchant txn report for Yesterday/"],
        RequestProcessor\Base::CARD_FSS_HDFC           => [
                                                            "/Please find attached All transaction Report & Settlement Report "
                                                            . "for transactions done/"
                                                          ],
        RequestProcessor\Base::CARD_FSS_SBI            => ["/Kindly find attached IPAY MIS file/"],
        RequestProcessor\Base::UPI_HULK                => ["/PFA transaction details for the date "
                                                            . "of  [0-9]{2}-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/"],
        RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY  => ["/Attached are the recon and refund files for [0-9]{2}\/[0-9]{2}\/[0-9]{2}/"],
        RequestProcessor\Base::PHONEPE                 => [".*/PFA the Settlement Report for transactions made through PhonePe/.*"],
        RequestProcessor\Base::UPI_JUSPAY              => ["/We have done the settlement for BAJAJ FINANCE/"],
        RequestProcessor\Base::NETBANKING_JSB          => ["/Dear Sir, Please find the details of payments made by our customers./"],
        RequestProcessor\Base::NETBANKING_IOB          => ["/This is a End Of Day Report email/"],
        RequestProcessor\Base::NETBANKING_KOTAK_V2     => ["/GBM CORPPG RECON REPORT FOR ENTITYCODE/"],
        RequestProcessor\Base::NETBANKING_RBL          => ["/Dear All,\nPlease find attachment for the Razorpay Payment Gateway Reconciliation File.\nThank You From RBL Bank/"],
        RequestProcessor\Base::WALLET_BAJAJ            => ["/Please find attached transection summary dated on/"],
    ];

    const GATEWAY_ATTACHMENT_COUNT = [
        RequestProcessor\Base::OLAMONEY                 => 1,
        RequestProcessor\Base::NETBANKING_AXIS          => 1,
        RequestProcessor\Base::NETBANKING_FEDERAL       => 1,
        RequestProcessor\Base::AXIS                     => 1,
        RequestProcessor\Base::FIRST_DATA               => 1,
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK        => 1,
        RequestProcessor\Base::HITACHI                  => 1,
        RequestProcessor\Base::UPI_AXIS                 => 2,
        RequestProcessor\Base::UPI_ICICI                => 1,
        RequestProcessor\Base::PAYZAPP                  => 1,
        RequestProcessor\Base::AIRTEL                   => 1,
        RequestProcessor\Base::NETBANKING_SIB           => 1,
        RequestProcessor\Base::NETBANKING_SCB           => 1,
        RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY   => 2,
        RequestProcessor\Base::PHONEPE                  => 1,
        RequestProcessor\Base::HDFC_DEBIT_EMI           => 1,
        RequestProcessor\Base::NETBANKING_SVC           => 2,
        RequestProcessor\Base::NETBANKING_JSB           => 1,
        RequestProcessor\Base::NETBANKING_FSB           => 1,
        RequestProcessor\Base::NETBANKING_IOB           => 1,
        RequestProcessor\Base::NETBANKING_JKB           => 1,
        RequestProcessor\Base::NETBANKING_KOTAK_V2      => 1,
        RequestProcessor\Base::NETBANKING_DCB           => 1,
        RequestProcessor\Base::NETBANKING_DLB           => 1,
        RequestProcessor\Base::NETBANKING_ICICI         => 1,
        RequestProcessor\Base::NETBANKING_RBL           => 1,
        RequestProcessor\Base::CARDLESS_EMI_ZESTMONEY   => 1,
        RequestProcessor\Base::NETBANKING_BDBL          => 1,
        RequestProcessor\Base::NETBANKING_SARASWAT      => 1,
        RequestProcessor\Base::NETBANKING_UCO           => 1,
    ];

    const AUTOMATIC_FETCHING_ENABLED_GATEWAYS = [RequestProcessor\Base::NETBANKING_SBI, RequestProcessor\Base::WALLET_BAJAJ];

    const WHITELISTED_EMAIL_FOR_ART = ["finances.recon@mg.razorpay.com", "art-recon@mg.razorpay.com"];

    // Add here too when being added in Validator::ACCEPTED_EXTENSIONS_MAP
    const SUPPORTED_ZIP_EXTENSIONS = ['zip', '7z'];

    // Max allowed file size - 35M (30*1024*1024).
    const MAX_FILE_SIZE = 36700160;

    // For batch service migrated gateways,
    // keeping max size = 96 MB (i.e 96*1024*1024)
    // As per batch team, their max limit is 100 MB currently.
    const MAX_FILE_SIZE_FOR_BATCH_SERVICE = 100663296;

    const FORCE_UPDATE_ALLOWED = [
        RequestProcessor\Base::REFUND_ARN,
        RequestProcessor\Base::PAYMENT_ARN,
        RequestProcessor\Base::PAYMENT_AUTH_CODE,
    ];

    const MANUAL_INPUT_RULES = [
        RequestProcessor\Base::ATTACHMENT_COUNT         => 'required|integer|min:0|max:10',
        RequestProcessor\Base::GATEWAY                  => 'required|custom',
        RequestProcessor\Base::FORCE_UPDATE             => 'sometimes|custom',
        RequestProcessor\Base::FORCE_AUTHORIZE          => 'sometimes',
        RequestProcessor\Base::FORCE_AUTHORIZE . '.*'   => 'sometimes|public_id'
    ];

    const UPDATE_UPI_RECON_DATA_RULES = [
        'payment_id'                    => 'required|string|size:14',
        'upi'                           => 'required|array',
        'upi.npci_reference_id'         => 'required',
        'upi.gateway_payment_id'        => 'sometimes',
        'upi.npci_txn_id'               => 'sometimes',
        'reconciled_type'               => 'required|string',
        'amount'                        => 'required',
        'reconciled_at'                 => 'required|filled|epoch',
        'gateway_settled_at'            => 'sometimes|epoch',
        'netbanking'                    => 'sometimes',
        'wallet'                        => 'sometimes',
        'card'                          => 'sometimes'
    ];

    const UPDATE_NETBANKING_RECON_DATA_RULES = [
        'payment_id'                                       => 'required|string|size:14',
        'netbanking'                                       => 'required|array',
        'wallet'                                           => 'sometimes',
        'upi'                                              => 'sometimes',
        'card'                                             => 'sometimes',
        'gateway_settled_at'                               => 'sometimes|epoch',
        'netbanking.gateway_transaction_id'                => 'sometimes',
        'netbanking.bank_transaction_id'                   => 'sometimes',
        'netbanking.bank_account_number'                   => 'sometimes',
        'netbanking.additional_data'                       => 'sometimes',
        'netbanking.additional_data.credit_account_number' => 'sometimes',
        'netbanking.additional_data.customer_id'           => 'sometimes',
        'reconciled_type'                                  => 'required|string',
        'amount'                                           => 'required',
        'reconciled_at'                                    => 'required|filled|epoch',
    ];

    const UPDATE_CARD_RECON_DATA_RULES = [
        'payment_id'                                       => 'required|string|size:14',
        'netbanking'                                       => 'sometimes',
        'upi'                                              => 'sometimes',
        'gateway_settled_at'                               => 'sometimes|epoch',
        'wallet'                                           => 'sometimes|array',
        'card'                                             => 'required|array',
        'card.auth_code'                                   => 'required|string',
        'card.rrn'                                         => 'required|string',
        'card.arn'                                         => 'required|string',
        'card.gateway_fee'                                 => 'required|string',
        'card.gateway_service_tax'                         => 'required|string',
        'reconciled_type'                                  => 'required|string',
        'amount'                                           => 'required',
        'card.gateway_fee'                                 => 'required',
        'card.gateway_service_tax'                         => 'required',
        'reconciled_at'                                    => 'required|filled|epoch',
    ];

    const UPDATE_WALLET_RECON_DATA_RULES = [
        'payment_id'                                       => 'required|string|size:14',
        'netbanking'                                       => 'sometimes',
        'upi'                                              => 'sometimes',
        'card'                                             => 'sometimes',
        'gateway_settled_at'                               => 'sometimes|epoch',
        'wallet'                                           => 'required|array',
        'wallet.wallet_transaction_id'                     => 'sometimes|string',
        'reconciled_type'                                  => 'required|string',
        'amount'                                           => 'required',
        'reconciled_at'                                    => 'required|filled|epoch',
    ];

    const UPDATE_REFUND_RECON_DATA_RULES = [
        'refunds'                   => 'required|array',
        'upi'                       => 'sometimes',
        'mode'                      => 'required|string',
        'source'                    => 'required|string',
        'should_force_update_arn'   => 'required|boolean',
        'art_request_id'            => 'required',
        'gateway'                   => 'required',
    ];

    const UPDATE_UPS_GATEWAY_ENTITY_UPDATE_RULES = [
        'payment_id'                => 'required',
        'gateway'                   => 'required',
        'model'                     => 'required',
        'batch_id'                  => 'sometimes',
        'gateway_data'              => 'required|array',
    ];

    public function filterEmails(array $emailDetails)
    {
        $from = $emailDetails[RequestProcessor\Mailgun::FROM];
        $validEmailIds = RequestProcessor\Base::GATEWAY_SENDER_MAPPING;

        if (get_key_from_subarray_match($from, $validEmailIds) === null)
        {
            throw new Exception\ReconciliationException(
                'The sender email ID is not whitelisted.',
                [
                    RequestProcessor\Mailgun::EMAIL_DETAILS => $emailDetails
                ]
            );
        }
    }

    public function getExtensionFromContentType(string $contentType)
    {
        $extension = get_key_from_subarray_match($contentType, self::ACCEPTED_EXTENSIONS_MAP);

        return $extension;
    }

    public function validateHdfcEmail(array $emailDetails)
    {
        return $this->validateEmailSubject(
                    $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                    RequestProcessor\Base::HDFC);
    }

    public function validateKotakEmail(array $emailDetails)
    {
        return $this->validateEmailSubject(
                    $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                    RequestProcessor\Base::KOTAK);
    }

    public function validateAirtelEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::AIRTEL);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::AIRTEL);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::AIRTEL);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateFreechargeEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::FREECHARGE);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::FREECHARGE);

        return ($validSubject and $validBody);
    }

    public function validateOlamoneyEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::OLAMONEY);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::OLAMONEY);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::OLAMONEY);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validatePayzappEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::PAYZAPP);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::PAYZAPP);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::PAYZAPP);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateNetbankingAxisEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
                                    $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                                    RequestProcessor\Base::NETBANKING_AXIS);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_AXIS);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_AXIS);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateNetbankingSibEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_SIB);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_SIB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_SIB);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateNetbankingSvcEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_SVC);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_SVC);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingJkbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_JKB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_JKB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingFsbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_FSB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_FSB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingJsbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_JSB);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_JSB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_JSB);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateNetbankingScbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
        RequestProcessor\Base::NETBANKING_SCB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_SCB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validatePhonepeEmail(array $emailDetails)
    {
        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::PHONEPE);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::PHONEPE);

        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::PHONEPE);

        return ($validAttachmentCount and $validBody and $validSubject);
    }

    public function validateNetbankingBobEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_BOB);

        return $validSubject;
    }

    public function validateNetbankingCsbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_CSB);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_CSB);

        return ($validSubject and $validBody);
    }

    public function validateNetbankingIciciEmail(array $emailDetails): bool
    {
        $validSubject = $this->validateEmailSubject(
                            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                            RequestProcessor\Base::NETBANKING_ICICI);

        $validAttachmentCount = $this->validateAttachmentCount(
                                    $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
                                    RequestProcessor\Base::NETBANKING_ICICI);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingSbiEmail(array $emailDetails)
    {

        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_SBI);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_SBI);


        //
        // There isn't a need to validate the attachment count because
        // validateAttachments already validates a non zero value.
        // In this case, the number is attachments is variable.
        //
        return ($validSubject and $validBody);
    }

    public function validateNetbankingFederalEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
                            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                            RequestProcessor\Base::NETBANKING_FEDERAL);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_FEDERAL);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_FEDERAL);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateNetbankingKvbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_KVB);
        return ($validSubject);
    }

    public function validateVirtualAccKotakEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
                            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                            RequestProcessor\Base::VIRTUAL_ACC_KOTAK);

        $validBody = $this->validateEmailBody(
                            $emailDetails[RequestProcessor\Mailgun::BODY],
                            RequestProcessor\Base::VIRTUAL_ACC_KOTAK);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::VIRTUAL_ACC_KOTAK);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateAxisEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::AXIS);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::AXIS);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::AXIS);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateFirstDataEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::FIRST_DATA);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::FIRST_DATA);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::FIRST_DATA);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateHitachiEmail(array $emailDetails): bool
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::HITACHI);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::HITACHI);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::HITACHI);

        return (($validSubject === true) and
            ($validAttachmentCount === true) and
            ($validBody === true));
    }

    public function validateUpiIciciEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
                             $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                    RequestProcessor\Base::UPI_ICICI);

        $validBody = $this->validateEmailBody(
                          $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
                 RequestProcessor\Base::UPI_ICICI);

        $validAttachmentCount = $this->validateAttachmentCount(
                                $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
                       RequestProcessor\Base::UPI_ICICI);

        return ($validSubject and $validAttachmentCount and $validBody);
    }

    public function validateUpiHdfcEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::UPI_HDFC);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::UPI_HDFC);

        return ($validSubject and $validBody);
    }

    public function validateNetbankingCorporationEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_CORPORATION);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::NETBANKING_CORPORATION);

        return ($validBody);
    }

    public function validateNetbankingAllahabadEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_ALLAHABAD);

        return ($validSubject);
    }

    public function validateCardFssHdfcEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::CARD_FSS_HDFC);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::CARD_FSS_HDFC);

        return ($validSubject and $validBody);
    }

    public function validateCardFssSbiEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::CARD_FSS_SBI);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::CARD_FSS_SBI);

        return ($validSubject and $validBody);
    }

    public function validateEmandateAxisEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::EMANDATE_AXIS);

        return $validSubject;
    }

    public function validateCardlessEmiEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY);

        return ($validSubject and $validBody);
    }

    public function validateCardlessEmiZestMoneyEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::CARDLESS_EMI_ZESTMONEY);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::CARDLESS_EMI_ZESTMONEY);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateBajajfinservEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::BAJAJFINSERV);

        return ($validSubject);
    }

    public function validateHdfcDebitEmiEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::HDFC_DEBIT_EMI);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::HDFC_DEBIT_EMI);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateUpiJuspayEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::UPI_JUSPAY);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY_HTML_TEXT],
            RequestProcessor\Base::UPI_JUSPAY);

        return ($validSubject and $validBody);
    }

    public function validateNetbankingIobEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_IOB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_IOB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingKotakV2Email(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_KOTAK_V2);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_KOTAK_V2);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingDcbEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_DCB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_DCB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingDLBEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_DLB);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_DLB);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingRBLEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_RBL);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_RBL);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingBdblEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_BDBL);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base:: NETBANKING_BDBL);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateNetbankingSaraswatEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_SARASWAT);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base:: NETBANKING_SARASWAT);

        return ($validSubject and $validAttachmentCount);
    }
    public function validateNetbankingUcoEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::NETBANKING_UCO);

        $validAttachmentCount = $this->validateAttachmentCount(
            $emailDetails[RequestProcessor\Base::ATTACHMENT_COUNT],
            RequestProcessor\Base::NETBANKING_UCO);

        return ($validSubject and $validAttachmentCount);
    }

    public function validateEmerchantpayEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
            $emailDetails[RequestProcessor\Mailgun::SUBJECT],
            RequestProcessor\Base::EMERCHANTPAY);

        return ($validSubject);
    }

    /**
     * For emails without attachments, but links, we allow
     * zero attachments during the initial validation.
     * After we get the attachments from the link, we validate
     * it again.
     *
     * @param array $input
     * @param bool  $allowZeroAttachments
     *
     * @throws Exception\ReconciliationException
     */
    public function validateAttachments(array & $input, bool $allowZeroAttachments = false)
    {
        //
        // Gets all the attachments found in the input by checking the number of
        // input keys starting with 'attachment-'.
        // Excludes 'attachment-count'.
        //
        $foundAttachments = array_filter(
            $input,
            function($key)
            {
                return (strpos($key, 'attachment-') === 0) and
                       (strpos($key, RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT) === false);
            },
            ARRAY_FILTER_USE_KEY
        );

        $foundAttachmentsCount = count($foundAttachments);

        //
        // In link based emails, we don't have the attachments at
        // this point. Hence, it'll be 0. This is fine, since we
        // update the attachment-count at a later point.
        //
        // Otherwise, there should be at least 1 attachment present.
        //
        if (($foundAttachmentsCount === 0) and
            ($allowZeroAttachments === false))
        {
            throw new Exception\ReconciliationException(
                'No attachments found in the input.'
            );
        }

        // Sets 'attachment-count' if not present and returns.
        // If present, converts it to int.
        if (isset($input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT]) === false)
        {
            $input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT] = $foundAttachmentsCount;
        }
        else
        {
            $input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT] = intval($input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT]);

            // The input's attachment-count and found attachments count should be equal.
            if ($input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT] !== $foundAttachmentsCount)
            {
                throw new Exception\ReconciliationException(
                    'The number of attachments found, does not match with the attachment-count input',
                    [
                        'attachments_found' => $foundAttachmentsCount,
                        'attachment_count' => $input[RequestProcessor\Base::ATTACHMENT_HYPHEN_COUNT]
                    ]
                );
            }
        }
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension and mime type combination is as expected.
     *
     * @param array $fileDetails
     * @param bool $forwardToBatchService
     * @return bool true if validation in successful, otherwise, false.
     */
    public function validateFile(array $fileDetails, bool $forwardToBatchService)
    {
        // Extensions are in uppercase sometimes.
        $extension = strtolower($fileDetails['extension']);
        $mimeType = strtolower($fileDetails['mime_type']);
        $fileSize = $fileDetails['size'];

        if (($this->validateExtensionMimeType($extension, $mimeType) === true) and
            ($this->validateFileSize($fileSize, $forwardToBatchService) === true))
        {
            return true;
        }

        return false;
    }

    public function validateManualInput(array $input)
    {
        (new JitValidator)->rules(self::MANUAL_INPUT_RULES)
                          ->caller($this)
                          ->input($input)
                          ->validate();
    }

    public function validateUpdateUpiReconData(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_UPI_RECON_DATA_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

      public function validateUpdateCardReconData(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_CARD_RECON_DATA_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

    public function validateUpdateNetbankingReconData(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_NETBANKING_RECON_DATA_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

    public function validateUpdateWalletReconData(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_WALLET_RECON_DATA_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

    public function validateUpdateRefundReconData(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_REFUND_RECON_DATA_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

    /** Validates the gateway entity update request message
     * @param array $input
     */
    public function validateUpsGatewayEntityUpdate(array $input)
    {
        (new JitValidator)->rules(self::UPDATE_UPS_GATEWAY_ENTITY_UPDATE_RULES)
            ->caller($this)
            ->input($input)
            ->validate();
    }

    public function validateGateway($attribute, $value, $parameters)
    {
        if (isset(RequestProcessor\Base::GATEWAY_SENDER_MAPPING[$value]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Invalid value for ' . RequestProcessor\Base::GATEWAY,
                    RequestProcessor\Base::GATEWAY,
                    [
                        RequestProcessor\Base::GATEWAY => $value
                    ]
            );
        }
    }

    public function validateForceUpdate($attribute, $value, $parameters)
    {
        $valid = false;

        if (is_array($value) === true)
        {
            if (empty($value) === true)
            {
                $valid = true;
            }
            else
            {
                $diff = array_diff($value, self::FORCE_UPDATE_ALLOWED);
                $valid = (count($diff) === 0);
            }
        }

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid value for ' . RequestProcessor\Base::FORCE_UPDATE
            );
        }
    }

    public function validateExtensionMimeType(string $extension, string $mimeType)
    {
        $acceptedExtensionsMap = self::ACCEPTED_EXTENSIONS_MAP;

        if ((isset($acceptedExtensionsMap[$extension]) === false) or
            (in_array($mimeType, $acceptedExtensionsMap[$extension], true) === false))
        {
            return false;
        }

        return true;
    }

    protected function validateFileSize(int $fileSize, bool $forwardToBatchService)
    {
        $maxAllowedFileSize = ($forwardToBatchService === true) ? self::MAX_FILE_SIZE_FOR_BATCH_SERVICE : self::MAX_FILE_SIZE;

        if ($fileSize > $maxAllowedFileSize)
        {
            return false;
        }

        return true;
    }

    protected function validateEmailSubject(string $subject, string $gateway)
    {
        $regexArray = self::GATEWAY_SUBJECT_REGEX[$gateway];

        foreach ($regexArray as $regex)
        {
            if (preg_match($regex, $subject) === 1)
            {
                return true;
            }
        }

        $this->trace->debug(
            TraceCode::RECON_EMAIL_VALIDATION_FAILED,
            [
                'subject'       => $subject,
                'regex_array'   => $regexArray,
                'gateway'       => $gateway,
            ]);

        return false;
    }

    protected function validateEmailBody(string $body, string $gateway)
    {
        $regexArray = self::GATEWAY_BODY_REGEX[$gateway];

        foreach ($regexArray as $regex)
        {
            if (preg_match($regex, $body) === 1)
            {
                return true;
            }
        }

        $this->trace->debug(
            TraceCode::RECON_EMAIL_VALIDATION_FAILED,
            [
                'email_body'    => $body,
                'regex_array'   => $regexArray,
                'gateway'       => $gateway,
            ]);

        return false;
    }

    protected function validateAttachmentCount(int $attachmentCount, string $gateway)
    {
        return (self::GATEWAY_ATTACHMENT_COUNT[$gateway] === $attachmentCount);
    }

    public function validateCustom($func, $attribute, $value, $parameters)
    {
        $message = 'function name should start with validate : ' . $func;

        assertTrue (strpos($func, 'validate') === 0, $message);

        $this->$func($attribute, $value, $parameters);
    }

    public function isAutomaticFetchingEnabledForGateway($gateway, $emailRecipient){
        if(in_array($gateway, self::AUTOMATIC_FETCHING_ENABLED_GATEWAYS) && in_array($emailRecipient, self::WHITELISTED_EMAIL_FOR_ART)){
            return true;
        }
        return false;
    }
}
