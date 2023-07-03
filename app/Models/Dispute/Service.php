<?php

namespace RZP\Models\Dispute;

use Mail;
use Request;
use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Dispute\File;
use RZP\Base\RuntimeManager;
use RZP\Models\Dispute\Reason;
use RZP\Models\{Base, Payment};
use RZP\Error\PublicErrorDescription;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Dispute\Constants as DisputeConstants;
use RZP\Services\Segment\Constants as SegmentConstants;

class Service extends Base\Service
{
    use FileHandlerTrait;

    // Bulk disputes file related constants
    const BULK_CREATE_ACTION              = 'bulk_create';
    const BULK_EDIT_ACTION                = 'bulk_edit';
    const RZP_DISPUTE_ID                  = 'rzp_dispute_id';
    const BULK_DISPUTE_CREATE_FILE_NAME   = 'bulk_disputes_create_status';
    const BULK_DISPUTE_EDIT_FILE_NAME     = 'bulk_disputes_edit_status';

    const SALESPOC_EMAIL_TO_EXCLUDE_FROM_CC = "businessops@razorpay.com";

    const BULK_DISPUTE_CREATE_DATE_FORMAT = 'd/m/Y H:i:s';

    // This is a limit on number of entries in bulk create/update file.
    // This can be removed once we handle files in Async batches
    const MAX_DISPUTE_ENTRIES = 5000;

    const BULK_CREATE_DISPUTES_COLUMNS = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Reason\Entity::REASON_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::AMOUNT,
        Entity::SKIP_EMAIL,
        Entity::INTERNAL_RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_CREATE_DISPUTES_COLUMNS_NEW = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Reason\Entity::REASON_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::GATEWAY_AMOUNT,
        Entity::GATEWAY_CURRENCY,
        Entity::SKIP_EMAIL,
        Entity::INTERNAL_RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_CREATE_DISPUTES_INGESTION_FOR_BANKS = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::GATEWAY_AMOUNT,
        Entity::GATEWAY_CURRENCY,
        Entity::SKIP_EMAIL,
        Entity::INTERNAL_RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_EDIT_DISPUTES_COLUMNS = [
        Entity::ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Entity::STATUS,
        Entity::SKIP_DEDUCTION,
        Entity::COMMENTS,
        Entity::INTERNAL_STATUS,
        Entity::DEDUCTION_REVERSAL_DELAY_IN_DAYS,
        Entity::RECOVERY_METHOD,
    ];

    // The 3 bulk dispute column name constants defined below BULK_CREATE_DISPUTES_COLUMNS_SILENT,
    // BULK_CREATE_DISPUTES_COLUMNS_NEW_SILENT, and BULK_EDIT_DISPUTES_COLUMNS_SILENT
    // are the bulk create and update file column names, with an additional column for the silent flag

    const BULK_CREATE_DISPUTES_COLUMNS_SILENT = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Reason\Entity::REASON_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::AMOUNT,
        Entity::SKIP_EMAIL,
        Entity::BACKFILL,
        Entity::INTERNAL_RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_CREATE_DISPUTES_COLUMNS_NEW_SILENT = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Reason\Entity::REASON_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::GATEWAY_AMOUNT,
        Entity::GATEWAY_CURRENCY,
        Entity::SKIP_EMAIL,
        Entity::BACKFILL,
        Entity::INTERNAL_RESPOND_BY,
        Entity::DEDUCT_AT_ONSET,
    ];

    const BULK_EDIT_DISPUTES_COLUMNS_SILENT = [
        Entity::ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Entity::STATUS,
        Entity::SKIP_DEDUCTION,
        Entity::COMMENTS,
        Entity::BACKFILL,
        Entity::INTERNAL_STATUS,
        Entity::DEDUCTION_REVERSAL_DELAY_IN_DAYS,
        Entity::RECOVERY_METHOD,
    ];

    // mapping of bulk action to file header values
    const ACTION_HEADERS_MAP = [
        self::BULK_CREATE_ACTION => [
            self::BULK_CREATE_DISPUTES_COLUMNS,
            self::BULK_CREATE_DISPUTES_COLUMNS_NEW,
            self::BULK_CREATE_DISPUTES_COLUMNS_SILENT,
            self::BULK_CREATE_DISPUTES_COLUMNS_NEW_SILENT,
            self::BULK_CREATE_DISPUTES_INGESTION_FOR_BANKS,
        ],
        self::BULK_EDIT_ACTION => [
            self::BULK_EDIT_DISPUTES_COLUMNS,
            self::BULK_EDIT_DISPUTES_COLUMNS_SILENT,
        ]
    ];

    const BACKFILL_COMMENT = "Note: This dispute is created by backfilling.";

    public function create(array $input, string $paymentId, Payment\Entity $payment = null): array
    {
        if ($payment === null)
        {
            $payment = $this->repo->payment->findByPublicId($paymentId);
        }

        (new Validator)->validateInputBeforeBuild($input);

        $reason = $this->repo->dispute_reason->findOrFail($input[Entity::REASON_ID]);

        $this->addBackfillIfNotPresent($input);

        $dispute = $this->core()->create($payment, $reason, $input);

        return $dispute->toArrayAdmin();
    }

    public function update(string $id, array $input): array
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

        $this->addBackfillIfNotPresent($input);

        if ($this->auth->isAdminAuth() === true)
        {
            $dispute = $this->core()->update($dispute, $input);

            return $dispute->toArrayAdmin();
        }
        else if (($this->auth->isPrivateAuth() === true) or
                 ($this->auth->isProxyAuth() === true))
        {
            return $this->core()->updateFilesAndInputForMerchant($dispute, $input);
        }
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function bulkCreate(array $input)
    {
        $startTime = millitime();

        $this->trace->info(
            TraceCode::DISPUTE_BULK_CREATE_REQUEST,
            [
                'input'      => $input,
            ]);

        RuntimeManager::setTimeLimit(300);

        $data = $this->validateAndGetFileData($input, self::BULK_CREATE_ACTION);

        $outputFileData = $this->createDisputes($data);

        $url = (new File\Service)->generateFile($outputFileData, self::BULK_DISPUTE_CREATE_FILE_NAME);

        $timeTaken = millitime() - $startTime;

        $this->trace->info(
            TraceCode::BULK_ACTION_RESPONSE_TIME,
            [
                'action'          => 'create_dispute',
                'time_taken'      => $timeTaken,
            ]);

        return [
            'link' => $url,
        ];
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function bulkUpdate(array $input)
    {
        $this->trace->info(
            TraceCode::DISPUTE_BULK_EDIT_REQUEST,
            [
                'input'      => $input,
            ]);

        $data = $this->validateAndGetFileData($input, self::BULK_EDIT_ACTION);

        $orderKeys = $data[0];

        $outputKeys   = $orderKeys;
        $outputKeys[] = Constants::ERRORS;

        $outputFileData   = [];
        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $dispute = $this->repo->dispute->findOrFail($input[Entity::ID]);

                if ($dispute->isClosed() === true)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        PublicErrorDescription::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE
                    );
                }

                $editInput = $this->prepareInputForEdit($input);

                $this->app['basicauth']->setMerchant($dispute->merchant);

                $this->core()->update($dispute, $editInput);

                $row[] = '';
            }
            catch (\Exception $e)
            {
                $row[] = $e->getMessage();

                $this->trace->traceException($e);
            }

            $outputFileData[] = $row;
        }

        $url = (new File\Service)->generateFile($outputFileData, self::BULK_DISPUTE_EDIT_FILE_NAME);

        return [
            'link' => $url,
        ];
    }

    public function getCreatedAtFromDisputeId($id): array {
        Entity::verifyIdAndStripSign($id);
        return $this->repo->dispute->getCreatedAtFromDisputeId($id);
    }

    public function fetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $disputes = $this->repo->dispute->fetch($input, $merchantId);

        return $disputes->toArrayPublic();
    }

    public function getCountForFetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        return $this->repo->dispute->getCountForFetchMultiple($input, $merchantId);
    }

    public function migrateOldAdjustments($file): array
    {
        return $this->core()->migrateOldAdjustments($file);
    }

    public function createReason(array $input): array
    {
        $reason = (new Reason\Core)->create($input);

        return $reason->toArrayPublic();
    }

    public function fetch(string $id, array $input = []): array
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant, $input);

        if ($this->app['basicauth']->isExpress() === true)
        {
            $res = $dispute->toArrayAdmin();

            $this->trace->info(
                TraceCode::DISPUTE_FETCH_REQUEST_EXPRESS,
                [
                    'response'  => $res,
                ],
            );

            return $res;
        }

        return $dispute->toArrayPublicWithExpand();
    }

    public function deleteFile(string $id, string $fileId)
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

        (new File\Core)->deleteFile($dispute, $fileId);
    }

    public function getFiles(string $id)
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

        return (new File\Core)->getFilesForEntity($dispute);
    }

    public function getDefaultDisputeEmails(string $merchantId) : array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->getDefaultEmailsForDispute($merchant);

        return $emails;
    }

    public function addSalesPOCToCCEmails($merchantId, $ccEmails)
    {
        try
        {
            $salesPOCEmailId = $this->app['salesforce']->getSalesPOCForMerchantID($merchantId);

            if ($salesPOCEmailId !== self::SALESPOC_EMAIL_TO_EXCLUDE_FROM_CC)
            {
                array_push($ccEmails, $salesPOCEmailId);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Logger::ERROR,
                TraceCode:: ERROR_IN_FETCHING_SALES_POC,
                [
                    'merchantId' => $merchantId,
                ]);
        }

        return array_unique($ccEmails);
    }

    public function getCCEmailsWithSalesPOC($merchantId)
    {
        $ccEmails = $this->getDefaultDisputeEmails($merchantId);

        return $this->addSalesPOCToCCEmails($merchantId, $ccEmails);
    }

    public function getSalesPOCEmailId($merchantId)
    {
        try
        {
            $salesPOCEmailId = $this->app['salesforce']->getSalesPOCForMerchantID($merchantId);

            if ($salesPOCEmailId !== self::SALESPOC_EMAIL_TO_EXCLUDE_FROM_CC)
            {
                return $salesPOCEmailId;
            }

            return null;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Logger::ERROR,
                                         TraceCode:: ERROR_IN_FETCHING_SALES_POC,
                                         [
                                             'merchantId' => $merchantId,
                                         ]);

            return null;
        }
    }

    /**
     * @param array $input
     * @param string $action
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAndGetFileData(array $input, string $action)
    {
        $validator = new Validator;

        $validator->validateBulkDisputeRequest($input);

        $file = $input[File\Core::FILE];

        $validator->validateBulkDisputesFile($file);

        $data = (new File\Service)->getFileData($file);

        if (count($data) > self::MAX_DISPUTE_ENTRIES)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Max. of ' . self::MAX_DISPUTE_ENTRIES . ' entries is/are allowed in a file'
            );
        }

        $headerMatch = false;

        if (array_key_exists($action, self::ACTION_HEADERS_MAP) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid value of action ' . $action);
        }

        $headersList = self::ACTION_HEADERS_MAP[$action];
        foreach ($headersList as $headers)
        {
            if ($validator->validateArrayEqual($data[0], $headers) === true)
            {
                $headerMatch = true;
                break;
            }
        }

        if ($headerMatch === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File Header columns do not match expected values');
        }

        return $data;
    }

    /**
     * @param string $network
     * @param string $networkCode
     * @param string $reasonCode
     * @return array
     * @throws Exception\RecoverableException
     */
    public function getDisputeReasonEntity(string $network, string $networkCode, string $reasonCode) : array
    {
        // special case need to be handled
        // there exist two reasons with network RUPAY and network code 1065
        // old one should be deleted, but it can't de deleted because we are already having disputes with that reason
        // product team will come with a proper handling for this case
        if (($network ===  DisputeConstants::NETWORK_RUPAY ) and ($networkCode === DisputeConstants::GATEWAY_CODE_1065) and empty($reasonCode) === true){
            $reasonCode = DisputeConstants::DISPUTE_REASON_CODE_ACCOUNT_DEBITED_NO_TRANSACTION_CONFIRMATION;
        }

        // Fetching Reason ID from dispute_reasons table
        $reasons = (new Reason\Service())->getReasonFromAttributes($network, $networkCode, $reasonCode);

        if (count($reasons) !== 1)
        {
            throw new Exception\RecoverableException(
                'There are no entries/more than 1 entries in DB for the given combination of network_code and reason_code'
            );
        }

        return $reasons[0];
    }

    /**
     * @param string $disputeReasonId
     * @param array $input
     * @return array
     */
    public function prepareInputForCreate(string $disputeReasonId, array $input) : array
    {
        $input[Entity::REASON_ID] = $disputeReasonId;
        $this->addBackfillIfNotPresent($input);

        unset($input[Entity::PAYMENT_ID]);
        unset($input[Reason\Entity::NETWORK]);
        unset($input[Reason\Entity::NETWORK_CODE]);
        unset($input[Reason\Entity::REASON_CODE]);

        return $input;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function prepareInputForEdit(array $input) : array
    {
        $this->addBackfillIfNotPresent($input);

        $this->updateBackfillComment($input);

        unset($input[Entity::ID]);

        $status = $input[Entity::STATUS] ?? null;

        if ($status !== Status::LOST)
        {
            unset($input[Entity::SKIP_DEDUCTION]);

            if ($input[Entity::BACKFILL] === false)
            {
                unset($input[Entity::COMMENTS]);
            }
        }
        else if ($input[Entity::SKIP_DEDUCTION] === true)
        {
            $comment = $input[Entity::COMMENTS];

            if (strlen($comment) < 5 or strlen($comment) > 255)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'comment should be a min. of 5 characters and max. of 255 characters'
                );
            }
        }
        return $input;
    }

    private function updateBackfillComment(array &$input)
    {
        if ($input[Entity::BACKFILL] === false)
        {
            return;
        }

        if ((array_key_exists(Entity::COMMENTS, $input) === false))
        {
            $input[Entity::COMMENTS] = self::BACKFILL_COMMENT;
        }
        else
        {
            $comment = strval($input[Entity::COMMENTS]);

            if (strlen($comment) === 0)
            {
                $input[Entity::COMMENTS] = self::BACKFILL_COMMENT;
            }
            else
            {
                $input[Entity::COMMENTS] = $comment . PHP_EOL . self::BACKFILL_COMMENT;
            }
        }
    }

    private function addBackfillIfNotPresent(array &$input)
    {
        if (array_key_exists(Entity::BACKFILL, $input) === false)
        {
            $input[Entity::BACKFILL] = false;

            return;
        }

        $val = $input[Entity::BACKFILL];

        if (is_bool($val) === false)
        {
            throw new Exception\RecoverableException(
                'backfill value must be a boolean'
            );
        }
    }

    /**
     * Validates each column of the file and converts it to necessary format
     *
     * @param array $row
     * @param array $keys
     * @return array
     */
    private function convertFileRowToMap(array $row, array $keys)
    {
        $fileInput = [];

        foreach ($keys as $key => $value)
        {
            $fileInput[$value] = $row[$key];
        }

        $input = [];

        foreach ($fileInput as $value => $res)
        {
            // To handle variations in csv and excel files, empty is converted to null
            $res = (empty($res) === false) ? trim(stringify($res)) : null;

            $func = 'formatValue' . studly_case($value);

            if (method_exists($this, $func))
            {
                $res = $this->$func($res, $input, $fileInput);
            }

            if ($res !== null)
            {
                $input[$value] = $res;
            }
        }

        return $input;
    }

    public function formatValueId($res)
    {
        if (empty($res) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Dispute ID cant be empty'
            );
        }

        return $res;
    }

    public function formatValuePaymentId($res)
    {
        if (empty($res) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment ID cant be empty'
            );
        }

        return $res;
    }

    public function formatValueReasonCode($res)
    {
        if (empty($res) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'reason_code cant be empty'
            );
        }

        $validCode = strtolower($res);
        $validCode = snake_case($validCode);

        if ($res !== $validCode)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid reason_code. Should be snake case with all smalls'
            );
        }

        return $res;
    }

    public function formatValueNetworkCode($res, array &$input)
    {
        if (empty($res) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'network_code cant be empty'
            );
        }

        $networkCode = array_map('trim', explode('-', $res));

        if (count($networkCode) !== 2)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid network_code format. Ex. Visa-85'
            );
        }

        $network = (new Reason\Validator)->validateAndFetchFormattedNetwork($networkCode[0]);

        $input[Reason\Entity::NETWORK] = $network;

        $res = $networkCode[1];

        return $res;
    }

    public function formatValuePhase($res)
    {
        if (empty($res) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'phase cant be empty'
            );
        }

        if (Phase::exists($res) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid phase \'' . $res . '\''
            );
        }

        return $res;
    }

    public function formatValueStatus($res)
    {
        $status = strtolower($res);

        return $status;
    }

    public function formatValueRaisedOn($res)
    {
        if (empty($res) === true)
        {
            $res = Carbon::now(Timezone::IST)->format('d/m/Y');
        }

        // Creation at beginning of the day IST
        $res .= ' 00:00:00';

        try
        {
            $res = Carbon::createFromFormat(self::BULK_DISPUTE_CREATE_DATE_FORMAT, $res, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            // Because default message thrown is incomprehensible
            throw new Exception\BadRequestValidationFailureException(
                'Invalid raised_on date. Please provide in d/m/Y format'
            );
        }

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($currentTime < $res)
        {
            throw new Exception\BadRequestValidationFailureException(
                'raised_on day cannot be greater than current day'
            );
        }

        return $res;
    }

    public function formatValueDeductionReversalDelayInDays($res, array &$input, array &$fileInput)
    {
        if (empty($res) === true)
        {
            return $res;
        }

        $input[Entity::DEDUCTION_REVERSAL_AT] = time() + (84600* (int)$res);

        return null;

    }

    public function formatValueDeductAtOnset($res, array &$input, array &$fileInput)
    {
        return (new Validator)->validateCustomBoolean($res);
    }

    public function formatValueInternalRespondBy($res, array &$input, array &$fileInput)
    {
        if (empty($res) === true)
        {
            return $res;
        }

        //At the end of the day IST
        $res .= ' 23:59:59';

        try
        {
            $res = Carbon::createFromFormat(self::BULK_DISPUTE_CREATE_DATE_FORMAT, $res, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            // Because default message thrown is incomprehensible
            throw new Exception\BadRequestValidationFailureException(
                'Invalid expires_on date. Please provide in d/m/Y format'
            );
        }
        return $res;
    }

    public function formatValueExpiresOn($res, array &$input, array &$fileInput)
    {
        if (empty($res) === true)
        {
            $res = Carbon::now(Timezone::IST)->addDays(9)->format('d/m/Y');
        }

        // Expires at the end of the day IST
        $res .= ' 23:59:59';

        try
        {
            $res = Carbon::createFromFormat(self::BULK_DISPUTE_CREATE_DATE_FORMAT, $res, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            // Because default message thrown is incomprehensible
            throw new Exception\BadRequestValidationFailureException(
                'Invalid expires_on date. Please provide in d/m/Y format'
            );
        }

        if (array_key_exists(Entity::BACKFILL, $fileInput) === true)
        {
            if ($this->formatValueBackfill($fileInput[Entity::BACKFILL]) === true)
            {
                $raisedOnTime = $this->formatValueRaisedOn($fileInput[Entity::RAISED_ON]);

                if ($raisedOnTime > $res)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'expires_on time cannot be less than or equal to raised_on time'
                    );
                }
                return $res;
            }
        }

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($currentTime >= $res)
        {
            throw new Exception\BadRequestValidationFailureException(
                'expires_on time cannot be less than or equal to current time'
            );
        }

        return $res;
    }

    public function formatValueContact($res)
    {
        if (empty($res) === false)
        {
            $number = new PhoneBook($res, true);

            if ($number->isValidNumber() === true)
            {
                $res = $number->format();
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid Contact number'
                );
            }
        }

        return $res;
    }

    public function formatValueSkipEmail($res)
    {
        return (new Validator)->validateCustomBoolean($res);
    }

    public function formatValueSkipDeduction($res)
    {
        return (new Validator)->validateCustomBoolean($res);
    }

    public function formatValueBackfill($res)
    {
        return (new Validator)->validateCustomBoolean($res);
    }

    public function initiateMerchantEmails()
    {
        return $this->core()->initiateMerchantEmails();
    }

    public function fetchDisputeReasonInternal(string $id): array
    {
        $disputeReason = $this->repo->dispute_reason->findOrFail($id);

        return $disputeReason->toArrayAdmin();
    }

    public function processDisputeRefunds(array $input)
    {
        return $this->core()->processDisputeRefunds($input);
    }

    public function initiateRiskAssessment()
    {
        return $this->core()->initiateRiskAssessment();
    }

    public function getDisputeDocumentTypesMetadata()
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DISPUTE_PRESENTMENT) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return $this->core()->getDisputeDocumentTypesMetadata();
    }

    public function patchDisputeContestById($disputeId, $input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DISPUTE_PRESENTMENT) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return $this->core()->patchDisputeContestById($disputeId, $input)->toArrayPublic();
    }


    public function postDisputeAcceptById($disputeId, $input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::EXCLUDE_DISPUTE_PRESENTMENT) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return $this->core()->postDisputeAcceptById($disputeId, $input)->toArrayPublic();
    }

    public function deductionReversalCron()
    {
        return $this->core()->deductionReversalCron();
    }

    public function createDisputes(array $data)
    {
        $orderKeys = $data[0];

        $outputFileData = [];

        $outputKeys   = $orderKeys;
        $outputKeys[] = self::RZP_DISPUTE_ID;
        $outputKeys[] = Constants::ERRORS;

        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $paymentId = $input[Entity::PAYMENT_ID];

                $payment = $this->repo->payment->findByPublicId($paymentId);

                $disputeReason = $this->getDisputeReasonEntity(
                    $input[Reason\Entity::NETWORK],
                    $input[Reason\Entity::NETWORK_CODE],
                    $input[Reason\Entity::REASON_CODE] ?? "",
                );

                $disputeReasonId = $disputeReason['id'];

                $createInput = $this->prepareInputForCreate($disputeReasonId, $input);

                $disputeEntity = $this->create($createInput, $paymentId, $payment);

                $row[] = $disputeEntity[Entity::ID];
                $row[] = '';
            }
            catch (\Exception $e)
            {
                $row[] = '';
                $row[] = $e->getMessage();
            }

            $outputFileData[] = $row;
        }

        return $outputFileData;

    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingDisputeDetails($input)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = $this->getSelfServeActionForFetchingDisputeDetail($input);

        if (isset($segmentProperties[SegmentConstants::SELF_SERVE_ACTION]) === true)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingDisputeDetailsFromDisputeId()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Dispute Details Searched';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function getSelfServeActionForFetchingDisputeDetail($input)
    {
        if (isset($input[Entity::PAYMENT_ID]) === true)
        {
            return 'Dispute Details Searched';
        }

        if ((isset($input[Entity::PHASE]) === true) or
            (isset($input[Entity::STATUS]) === true) or
            ((isset($input[Merchant\Constants::FROM]) === true) and
            (isset($input[Merchant\Constants::TO]) === true) and
            ($this->checkDurationInterval($input[Merchant\Constants::FROM], $input[Merchant\Constants::TO]) === true)))
        {
            return 'Dispute Details Filtered';
        }
    }

    private function checkDurationInterval($from, $to)
    {
        //By default duration is set as Past 90 days for Disputes in the Transactions Tab in PG Merchant Dashboard
        //Timestamp difference for this duration is 7862399.
        //We have to trigger the event whenever the duration is changed by the merchant.
        if (($to - $from == '7862399') and
            ($to == Carbon::today(Timezone::IST)->endOfDay()->getTimestamp()))
        {
            return false;
        }

        return true;
    }
}
