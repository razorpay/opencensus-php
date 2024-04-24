<?php


namespace RZP\Models\CardlessEmiNceAdjustment;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Mail\CardlessEmiNceAdjustment\FailAxioAdjustmentSettlement;
use RZP\Mail\CardlessEmiNceAdjustment\SuccessAxioAdjustmentSettlement;
use RZP\Mail\CardlessEmiNceAdjustment\SuccessPolicyBazaarAdjustmentSettlement;
use RZP\Mail\BankingAccount\StatementMail;
use RZP\Models\Adjustment;
use RZP\Models\Adjustment\Entity;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Converter;
use RZP\Reconciliator\FileProcessor;
use RZP\Trace\TraceCode;
use RZP\Constants as DefaultConstants;
use function Termwind\ValueObjects\pb;


class Service extends Base\Service
{
    protected $fileProcessor;
    const EMAIL_DETAILS   = 'email_details';
    const FROM            = 'from';
    const TO              = 'to';
    const SUBJECT         = 'subject';
    const RECIPIENT       = 'recipient';
    const TIMESTAMP       = 'timestamp';
    const BODY            = 'body';
    const BODY_HTML_TEXT  = 'body_html_text';
    const BODY_HTML       = 'body-html';
    const BODY_PLAIN      = 'body-plain';
    const STRIPPED_HTML   = 'stripped-html';
    const STRIPPED_TEXT   = 'stripped-text';
    const MESSAGE_HEADERS = 'message-headers';
    const ATTACHMENT_COUNT              = 'attachment_count';
    const ATTACHMENT_HYPHEN_COUNT       = 'attachment-count';
    const ATTACHMENT_HYPHEN_ONE         = 'attachment-1';
    const ATTACHMENT_HYPHEN_PREFIX      = 'attachment-';
    const SOURCE                        = 'source';
    const MAILGUN                 = 'mailgun';
    const FILE_DETAILS            = 'file_details';
    const INPUT_DETAILS           = 'input_details';

    public function initiateAdjustment(array $input)
    {
        $this->traceAdjustmentRequest($input);

        try
        {
            $summary = $this->processAdjustmentRequest($input);
        }
        catch (\Throwable $e)
        {

            $this->trace->traceException(
                $e, Trace::DEBUG, TraceCode::ADJUSTMENT_MAIL_REQUEST_FAILURE);

            // We do not throw an exception as route is hit via Mailgun,
            // and Mailgun will attempt retrying, which we don't want.
            return [];
        }

        return $summary;
    }

    /**
     * Request body, if sent via mail through Mailgun, is too large
     * to be parsed effectively on Splunk. So we unset the body params,
     * then trace everything else.
     * Other headers will be enough to identify the mail if needed.
     *
     * @param array $input Request body
     */
    protected function traceAdjustmentRequest(array $input)
    {
        unset($input[self::BODY_HTML]);
        unset($input[self::BODY_PLAIN]);
        unset($input[self::STRIPPED_HTML]);
        unset($input[self::STRIPPED_TEXT]);
        unset($input[self::MESSAGE_HEADERS]);

        $this->trace->info(
            TraceCode::ADJUSTMENT_MAIL_REQUEST,
            $input);
    }

    /**
     * Process the request and gets the files details accordingly.
     * Validate if adjustment is already processed for that day, if not
     * then calculate the adjustment amount.
     * Check the live balance for provider, if its greater than the adjustment amount
     * perform the adjustment.
     *
     * @param array  $input The input received from the route.
     * @param string $source
     *
     * @return array Summary of adjustment
     * @throws Exception Raised when if
     * 1. Email validation fails
     * 2. Adjustment already performed for that day
     * 3. Live balance for provider is less than the adjustment amount.
     * 4. Fail to send Acknowledgement mail
     */
    protected function processAdjustmentRequest(array $input)
    {
        $this->converter = new Converter();
        $adjustmentAmount = 0;
        $adjustmentFileDetails = $this->processFile($input);

        // There must be at least one file. Otherwise, error.
        if (empty(self::FILE_DETAILS) === true)
        {
            throw new Exception(
                'File details are empty.');
        }

        //Check for adjustment for the day for Axio mid
        $providerMid = $this->getProviderMid($adjustmentFileDetails[self::INPUT_DETAILS]);
        $merchantMid = $this->getMerchantMid($adjustmentFileDetails[self::INPUT_DETAILS]);

        $day  = Carbon::now(Timezone::IST);

        $description = Constants::MERCHANT_MAP_FOR_ADJUSTMENT_DESCRIPTION[$providerMid] . $day->toDateString();
        // check if adjustment is created for the day via other process or in-case duplicate request from cron.
        $adjustmentExists = $this->repo->adjustment->findAdjustmentByDescription($description, $providerMid);

        if ($adjustmentExists === true)
        {
            $this->trace->info(TraceCode::ADJUSTMENT_ALREADY_EXISTS, [
                "merchant_id" => $providerMid,
                "description" => $description
            ]);

            $this->sendMailForFailure($adjustmentAmount,$providerMid);
            return [];
        }

        //Check live balance
        $providerLiveBalance = $this->getMerchantLiveBalance($providerMid);


        //Process adjustment
        $excelArray = $this->converter->convertExcelToArray((array)$adjustmentFileDetails['file_details'],
            "",
            1,
            "",
            "");

        foreach ($excelArray['sheet0'] as $rowdata)
        {
            $adjustmentAmount=$adjustmentAmount + $rowdata['adjustment_amount']*100;
        }

        if($providerLiveBalance>=$adjustmentAmount){
            $collection = new PublicCollection();

            $posAdjPayload = [
                Adjustment\Entity::MERCHANT_ID => $merchantMid,
                Adjustment\Entity::AMOUNT      => intval($adjustmentAmount),
                Adjustment\Entity::CURRENCY    => Currency::INR,
                Adjustment\Entity::ENTITY_TYPE => DefaultConstants\Entity::OFFER,
                Adjustment\Entity::DESCRIPTION => Constants::MERCHANT_MAP_FOR_ADJUSTMENT_DESCRIPTION[$merchantMid].$day->toDateString(),
            ];

            $negAdjPayload = [
                Adjustment\Entity::MERCHANT_ID => $providerMid,
                Adjustment\Entity::AMOUNT      => intval(0 - $adjustmentAmount),
                Adjustment\Entity::CURRENCY    => Currency::INR,
                Adjustment\Entity::ENTITY_TYPE => DefaultConstants\Entity::OFFER,
                Adjustment\Entity::DESCRIPTION => $description,
            ];

            $response = $this->repo->transaction(function () use ($negAdjPayload, $posAdjPayload) {
                //$adj1 = (new Adjustment\Service)->addAdjustment($posAdjPayload);
                $adj2 = (new Adjustment\Service)->addAdjustment($negAdjPayload);
                return [$adj2];
            });

            foreach ($response as $arr)
            {
                $collection->push($arr);
            }
            try
            {
                $this->sendMailToProvider($adjustmentAmount,$response[0]['id']);
                //$this->sendMailForMerchant($adjustmentAmount,$response[1]['id']);
                return $collection;
            }
            catch (\Throwable $e)
            {

                $this->trace->traceException(
                    $e, Trace::DEBUG, TraceCode::ADJUSTMENT_MAIL_PROCESS_FAILURE);

                // We do not throw an exception as route is hit via Mailgun,
                // and Mailgun will attempt retrying, which we don't want.
                return [];
            }
        }
        else
        {
            try
            {
                $this->sendMailForFailure($adjustmentAmount,$providerMid);
            }
            catch (\Throwable $e)
            {

                $this->trace->traceException(
                    $e, Trace::DEBUG, TraceCode::ADJUSTMENT_MAIL_PROCESS_FAILURE);

                // We do not throw an exception as route is hit via Mailgun,
                // and Mailgun will attempt retrying, which we don't want.
                return [];
            }

        }
    }

    /**
     * Getting all files details is handled by this function when the
     * CardlessEmiNceAdjustment is called.
     *
     * @param array $input The input received from the route.
     * @return array Details of all the file received from the input.
     */
    protected function processFile(array $input): array
    {
        // Gets the email details and validates the email details.
        $this->inputDetails = $this->getEmailDetails($input);

        $this->filterEmails($this->inputDetails);

        $fileLocationType = FileProcessor::UPLOADED;


        $this->inputDetails[self::SOURCE] = self::MAILGUN;

        $allFilesDetails = $this->getFileDetailsFromInput(
            $this->inputDetails, $input, $fileLocationType);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $this->inputDetails,
        ];
    }

    protected function getEmailDetails($input)
    {
        //
        // Sender info is picked from the 'X-Original-Sender' header, instead
        // of 'sender' or 'from' headers.
        //
        // 'sender' will contain "settlement+{hash}@googlegroups.com", as the
        // mail is being forwarded to Mailgun through our settlements group.
        // 'From' may contain values like "HDFC Bank <payoutreport@hdfcbank.com",
        // formatted by the sender's email client.
        // 'X-Original-Sender' always contains just the email address.
        //

        $strippedHtml = $input[self::STRIPPED_HTML] ?? '';

        $from = $input['X-Original-Sender'] ?? $input['sender'];


        $inputDetails = [
            self::FROM           => strtolower($from),
            self::SUBJECT        => $input[self::SUBJECT],
            self::TO             => $input[self::RECIPIENT],
            self::TIMESTAMP      => $input[self::TIMESTAMP],
            self::BODY           => $input[self::STRIPPED_TEXT] ?? '',
            self::BODY_HTML_TEXT => html_entity_decode(strip_tags($strippedHtml)),
        ];

        //
        // Validates that attachments are present in the email.
        // We haven't parsed the email for attachments yet at this point.
        //
        $this->validateAttachments($input, false);

        $inputDetails[self::ATTACHMENT_COUNT] = $input[self::ATTACHMENT_HYPHEN_COUNT];

        return $inputDetails;
    }

    /**
     *
     * @param array $input
     * @param bool  $allowZeroAttachments
     *
     * @throws Exception
     */
    protected function validateAttachments(array & $input, bool $allowZeroAttachments = false)
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
                return (strpos($key, self::ATTACHMENT_HYPHEN_PREFIX) === 0) and
                    (strpos($key, self::ATTACHMENT_HYPHEN_COUNT) === false);
            },
            ARRAY_FILTER_USE_KEY
        );

        $foundAttachmentsCount = count($foundAttachments);

        if (($foundAttachmentsCount === 0) and
            ($allowZeroAttachments === false))
        {
            throw new BadRequestValidationFailureException(
                'No attachments found in the input.'
            );
        }

        // Sets 'attachment-count' if not present and returns.
        // If present, converts it to int.
        if (isset($input[self::ATTACHMENT_HYPHEN_COUNT]) === false)
        {
            $input[self::ATTACHMENT_HYPHEN_COUNT] = $foundAttachmentsCount;
        }
        else
        {
            $input[self::ATTACHMENT_HYPHEN_COUNT] = intval($input[self::ATTACHMENT_HYPHEN_COUNT]);

            // The input's attachment-count and found attachments count should be equal.
            if ($input[self::ATTACHMENT_HYPHEN_COUNT] !== $foundAttachmentsCount)
            {
                throw new BadRequestValidationFailureException(
                    'The number of attachments found, does not match with the attachment-count input',
                    [
                        'attachments_found' => $foundAttachmentsCount,
                        'attachment_count' => $input[self::ATTACHMENT_HYPHEN_COUNT]
                    ]
                );
            }
        }
    }

    protected function filterEmails(array $emailDetails)
    {
        $from = $emailDetails[self::FROM];
        //TO-DO Add all valid Emails
        $validEmailIds = ['capitalfloat.com@holistics.io'];

        if (in_array($from, $validEmailIds) === null)
        {
            throw new BadRequestValidationFailureException(
                'The sender email ID is not whitelisted.',
                [
                    self::EMAIL_DETAILS => $emailDetails
                ]
            );
        }
    }

    /**
     * @param   array        $inputDetails
     * @param   array        $input
     * @param   string       $fileLocationType
     *
     * @return array
     */
    protected function getFileDetailsFromInput(
        array $inputDetails,
        array $input,
        string $fileLocationType = FileProcessor::UPLOADED)
    {
        $fileProcessor = new FileProcessor;

        $allFilesDetails = [];
        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input[self::ATTACHMENT_HYPHEN_PREFIX . $attachmentNumber];

            $allFilesDetails[] = $fileProcessor->getFileDetails($file, $fileLocationType);

        }
        return $allFilesDetails[0];
    }

    protected function getMerchantLiveBalance(string $mid)
    {
        $merchantService = new \RZP\Models\Merchant\Service();
        return $merchantService->fetchBalance($mid);
    }

    private function sendMailToProvider(int $adjustmentAmount, string $adjustmentId)
    {
        $email = new SuccessAxioAdjustmentSettlement($adjustmentAmount,
            $adjustmentId);

        Mail::queue($email);
    }

    private function sendMailForMerchant(int $adjustmentAmount, string $adjustmentId)
    {
        $email = new SuccessPolicyBazaarAdjustmentSettlement($adjustmentAmount,
            $adjustmentId);

        Mail::queue($email);
    }

    private function sendMailForFailure(int $adjustmentAmount, string $merchantId)
    {
        $email = new FailAxioAdjustmentSettlement($adjustmentAmount,
            $merchantId);

        Mail::queue($email);
    }

    private function getProviderMid($inputDetails)
    {
        return  Constants::MERCHANT_MAP_FOR_ADJUSTMENT[Constants::AXIO];
    }

    private function getMerchantMid($inputDetails)
    {
        return  Constants::MERCHANT_MAP_FOR_ADJUSTMENT[Constants::POLICY_BAZAAR];
    }
}
