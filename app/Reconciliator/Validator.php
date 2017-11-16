<?php

namespace RZP\Reconciliator;

use RZP\Exception;
use RZP\Base\JitValidator;
use RZP\Reconciliator\RequestProcessor;

class Validator
{
    const ACCEPTED_EXTENSIONS_MAP = [
        'csv'   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'text/plain'],
        'txt'   => ['text/plain', 'application/octet-stream'],
        // Ensure that this is always above 'xlsx' because of `getExtensionFromContentType`
        'zip'   => ['application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'],
        'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/octet-stream'],
        // `text/plain` is being added here because HDFC sends CSV files with XLS extension. kthxbye
        // `application/CDFV2-unknown` is being sent for FirstData files. sigh.
        'xls'   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel',
                    'application/vnd.ms-office', 'application/octet-stream', 'text/plain',
                    'application/cdfv2-unknown'],
        'xlsb'  => [
            'application/excel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-office',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip',
            'application/octet-stream', 'application/vnd.oasis.opendocument.spreadsheet',
        ],
        'rpt'   => ['text/plain'],
        'dat'   => ['text/plain'],
    ];

    const GATEWAY_SUBJECT_REGEX = [
        RequestProcessor\Base::HDFC               => "/^'{0,1}Email MPR as of [0-9]{2}-"
                                            . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/",
        RequestProcessor\Base::KOTAK              => "/^PG Transaction File/",
        RequestProcessor\Base::OLAMONEY           => "/^Merchant Settlement File/",
        RequestProcessor\Base::FREECHARGE         => "/^Merchant (Transaction|Settlement) Report/",
        RequestProcessor\Base::NETBANKING_AXIS    => "/^MIS file for (0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}, "
                                            . "for all RazorPay & Payees : Payeespecific MIS\(FEBA\)/",
        RequestProcessor\Base::NETBANKING_ICICI   => "/^Payment Through Internet Banking Center Razorpay/",
        RequestProcessor\Base::NETBANKING_FEDERAL => "/^MIS Report File Dated "
                                            . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}---razorpay/",
        RequestProcessor\Base::AXIS               => "/^Axis Estatement [0-9]{2}-"
                                            . "(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-20[0-9]{2}/",
        RequestProcessor\Base::FIRST_DATA         => "/Statement for Merchant MID No. razorpay/",
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK  => "/^RAZOR_VA_REPORT$/",
    ];

    const GATEWAY_BODY_REGEX = [
        RequestProcessor\Base::OLAMONEY           => "/^Please find settlement report for /",
        RequestProcessor\Base::FREECHARGE         => "/Please view your (transaction|settlement) report/",
        RequestProcessor\Base::NETBANKING_AXIS    => "/Kindly find attached below the MIS for "
                                            . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}/",
        RequestProcessor\Base::NETBANKING_ICICI   => "/Please find below the payment report for the day./",
        RequestProcessor\Base::NETBANKING_FEDERAL => "/^MIS Report File Dated "
                                            . "(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/20[0-9]{2}/",
        RequestProcessor\Base::AXIS               => "/Please find attached the settlement file for today."
                                            . " You net amount settled is/",
        RequestProcessor\Base::FIRST_DATA         => "/the statement of transactions for MID (.)*razorpay/",
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK  => "/Please find the hourly report of Virtual Accounts./",
    ];

    const GATEWAY_ATTACHMENT_COUNT = [
        RequestProcessor\Base::OLAMONEY           => 1,
        RequestProcessor\Base::NETBANKING_AXIS    => 1,
        RequestProcessor\Base::NETBANKING_FEDERAL => 1,
        RequestProcessor\Base::AXIS               => 1,
        RequestProcessor\Base::FIRST_DATA         => 1,
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK  => 1,
    ];

    // Add here too when being added in Validator::ACCEPTED_EXTENSIONS_MAP
    const SUPPORTED_ZIP_EXTENSIONS = ['zip'];

    // Max allowed file size - 25M (25*1024*1024).
    const MAX_FILE_SIZE = 26214400;

    const FORCE_UPDATE_ALLOWED = [
        RequestProcessor\Base::REFUND_ARN
    ];

    const MANUAL_INPUT_RULES = [
        RequestProcessor\Base::ATTACHMENT_COUNT    => 'required|integer|min:0|max:10',
        RequestProcessor\Base::GATEWAY             => 'required|custom',
        RequestProcessor\Base::FORCE_UPDATE        => 'sometimes|custom',
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

    public function validateNetbankingIciciEmail(array $emailDetails)
    {
        $validSubject = $this->validateEmailSubject(
                                $emailDetails[RequestProcessor\Mailgun::SUBJECT],
                                RequestProcessor\Base::NETBANKING_ICICI);

        $validBody = $this->validateEmailBody(
            $emailDetails[RequestProcessor\Mailgun::BODY],
            RequestProcessor\Base::NETBANKING_ICICI);

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
                       (strpos($key, 'attachment-count') === false);
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
        if (isset($input['attachment-count']) === false)
        {
            $input['attachment-count'] = $foundAttachmentsCount;
        }
        else
        {
            $input['attachment-count'] = intval($input['attachment-count']);

            // The input's attachment-count and found attachments count should be equal.
            if ($input['attachment-count'] !== $foundAttachmentsCount)
            {
                throw new Exception\ReconciliationException(
                    'The number of attachments found, does not match with the attachment-count input',
                    [
                        'attachments_found' => $foundAttachmentsCount,
                        'attachment_count' => $input['attachment-count']
                    ]
                );
            }
        }
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension and mime type combination is as expected.
     *
     * @param $fileDetails
     * @return bool true if validation in successful, otherwise, false.
     */
    public function validateFile(array $fileDetails)
    {
        // Extensions are in uppercase sometimes.
        $extension = strtolower($fileDetails['extension']);
        $mimeType = strtolower($fileDetails['mime_type']);
        $fileSize = $fileDetails['size'];

        if (($this->validateExtensionMimeType($extension, $mimeType) === true) and
            ($this->validateFileSize($fileSize) === true))
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

    public function validateGateway($attribute, $value, $parameters)
    {
        if (isset(Orchestrator::GATEWAY_SENDER_MAPPING[$value]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Invalid value for' . Orchestrator::GATEWAY
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
                $diff = array_diff(self::FORCE_UPDATE_ALLOWED, $value);
                $valid = (count($diff) === 0);
            }
        }

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid value for ' . Orchestrator::FORCE_UPDATE
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

    protected function validateFileSize(int $fileSize)
    {
        if ($fileSize > self::MAX_FILE_SIZE)
        {
            return false;
        }

        return true;
    }

    protected function validateEmailSubject(string $subject, string $gateway)
    {
        $regex = self::GATEWAY_SUBJECT_REGEX[$gateway];

        if (preg_match($regex, $subject) === 1)
        {
            return true;
        }

        return false;
    }

    protected function validateEmailBody(string $body, string $gateway)
    {
        $regex = self::GATEWAY_BODY_REGEX[$gateway];

        if (preg_match($regex, $body) === 1)
        {
            return true;
        }

        return false;
    }

    protected function validateAttachmentCount(int $attachmentCount, string $gateway)
    {
        return (self::GATEWAY_ATTACHMENT_COUNT[$gateway] === $attachmentCount);
    }

    public function validateCustom($func, $attribute, $value, $parameters)
    {
        $message = 'function name should start with validate : ' . $func;

        assert (strpos($func, 'validate') === 0, $message);

        $this->$func($attribute, $value, $parameters);
    }
}
