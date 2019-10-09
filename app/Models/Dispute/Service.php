<?php

namespace RZP\Models\Dispute;

use Mail;
use Request;

use RZP\Exception;
use RZP\Mail\Base\Constants;
use RZP\Models\{Base, Payment};
use RZP\Models\Dispute\File;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Dispute\Reason;
use RZP\Mail\Dispute as DisputeMailer;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use FileHandlerTrait;

    // Bulk disputes file related constants
    const bulkCreateAction          = 'bulk_create';
    const bulkEditAction            = 'bulk_edit';
    const RZPDisputeID              = 'rzp_dispute_id';
    const bulkDisputeCreateFileName = 'bulk_disputes_create_status';
    const bulkDisputeEditFileName   = 'bulk_disputes_edit_status';

    const bulkCreateDisputesColumns = [
        Entity::PAYMENT_ID,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Reason\Entity::NETWORK_CODE,
        Reason\Entity::REASON_CODE,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::AMOUNT,
        Entity::MERCHANT_EMAILS,
        Entity::SKIP_EMAIL,
        Entity::CONTACT,
    ];

    const bulkEditDisputesColumns = [
        Entity::ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Entity::STATUS,
        Entity::SKIP_DEDUCTION,
        Entity::COMMENTS,
    ];

    const bulkCreateDisputesMailData = [
        Entity::ID,
        Entity::PAYMENT_ID,
        Entity::AMOUNT,
        Entity::GATEWAY_DISPUTE_ID,
        Entity::PHASE,
        Entity::RESPOND_BY,
        Entity::CONTACT,
    ];

    public function create(array $input, string $paymentId, Payment\Entity $payment = null): array
    {
        if ($payment === null)
        {
            $payment = $this->repo->payment->findByPublicId($paymentId);
        }

        (new Validator)->validateInputBeforeBuild($input);

        $reason = $this->repo->dispute_reason->findOrFail($input[Entity::REASON_ID]);

        $dispute = $this->core()->create($payment, $reason, $input);

        return $dispute->toArrayAdmin();
    }

    public function update(string $id, array $input): array
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

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
        $this->trace->info(
            TraceCode::DISPUTE_BULK_CREATE_REQUEST,
            [
                'input'      => $input,
            ]);

        $data = $this->validateInputAndGetFileData($input, self::bulkCreateAction);

        $orderKeys = $data[0];

        $outputFileData = $mailData = $merchantData = $disputeData = [];

        $outputKeys   = $orderKeys;
        $outputKeys[] = self::RZPDisputeID;
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

                $merchant = $payment->merchant;

                $createInput = $this->prepareInputForCreate($input);

                $disputeEntity = $this->create($createInput, $paymentId, $payment);

                $contact = empty($input[Entity::CONTACT]) ? $payment[Payment\Entity::CONTACT] ?? 'N/A' : $input[Entity::CONTACT];

                $disputeEntity[Entity::CONTACT] = $contact;

                // Prepares Mail body data
                if ($input[Entity::SKIP_EMAIL] === false)
                {
                    $emails = $this->core()->getEmailsForCreationMail($merchant, $input);

                    $merchantData[$disputeEntity[Entity::MERCHANT_ID]] = $merchant->getName();

                    $disputeData[$disputeEntity[Entity::ID]] = $this->getDisputeDataForMail($disputeEntity);

                    foreach ($emails as $email)
                    {
                        $mailData[$disputeEntity[Entity::MERCHANT_ID]][$email][] = $disputeEntity[Entity::ID];
                    }
                }

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

        $this->sendAggregatedEmails($mailData, $merchantData, $disputeData);

        $url = (new File\Service)->generateFile($outputFileData, self::bulkDisputeCreateFileName);

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

        $data = $this->validateInputAndGetFileData($input, self::bulkEditAction);

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

                $editInput = $this->prepareInputForEdit($input);

                $this->core()->update($dispute, $editInput);

                $row[] = '';
            }
            catch (\Exception $e)
            {
                $row[] = $e->getMessage();
            }

            $outputFileData[] = $row;
        }

        $url = (new File\Service)->generateFile($outputFileData, self::bulkDisputeEditFileName);

        return [
            'link' => $url,
        ];
    }

    public function fetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $disputes = $this->repo->dispute->fetch($input, $merchantId);

        return $disputes->toArrayPublic();
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

    public function fetch(string $id): array
    {
        $dispute = $this->repo->dispute->findByPublicIdAndMerchant($id, $this->merchant);

        return $dispute->toArrayPublic();
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

    /**
     * @param array $input
     * @param string $action
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateInputAndGetFileData(array $input, string $action)
    {
        $validator = new Validator;

        $validator->validateBulkDisputeRequest($input);

        $file = $input[File\Core::FILE];

        $validator->validateBulkDisputesFile($file);

        $data = (new File\Service)->getFileData($file);

        $headers = [];

        switch ($action)
        {
            case self::bulkCreateAction :
                $headers = self::bulkCreateDisputesColumns;
                break;

            case self::bulkEditAction :
                $headers = self::bulkEditDisputesColumns;
                break;
        }

        if ($validator->arrayEqual($data[0], $headers) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File Header columns do not match expected values'
            );
        }

        return $data;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\RecoverableException
     */
    public function prepareInputForCreate(array $input) : array
    {
        // Fetching Reason ID from dispute_reasons table
        $reasonId = (new Reason\Service())->getReasonIdFromAttributes(
            $input[Reason\Entity::NETWORK], $input[Reason\Entity::NETWORK_CODE], $input[Reason\Entity::REASON_CODE]);

        if (count($reasonId) !== 1)
        {
            throw new Exception\RecoverableException(
                'There are no entries/more than 1 entries in DB for the given combination of network_code and reason_code'
            );
        }

        // skipping email for each creation
        $input[Entity::SKIP_EMAIL] = true;
        $input[Entity::REASON_ID] = $reasonId[0];

        unset($input[Entity::CONTACT]);
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
        unset($input[Entity::ID]);

        if (empty($input[Entity::GATEWAY_DISPUTE_STATUS]) === true)
        {
            unset($input[Entity::GATEWAY_DISPUTE_STATUS]);
        }

        if ($input[Entity::STATUS] !== Status::LOST)
        {
            unset($input[Entity::SKIP_DEDUCTION]);
            unset($input[Entity::COMMENTS]);
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

    /**
     * Sends Aggregated Emails on merchant level
     *
     * @param array $mailData
     * @param array $merchantData
     * @param array $disputeData
     */
    public function sendAggregatedEmails(array $mailData, array $merchantData, array $disputeData)
    {
        foreach ($mailData as $merchantId => $data)
        {
            foreach ($data as $mailId => $disputeIds)
            {
                $bulkMailData[EntityConstants::MERCHANT][MerchantEntity::NAME] = $merchantData[$merchantId];
                $bulkMailData[EntityConstants::MERCHANT][MerchantEntity::EMAIL] = $mailId;
                $bulkMailData[Constants::DISPUTES] = [];

                $totalAmount = 0;

                foreach ($disputeIds as $id)
                {
                    $bulkMailData[Constants::DISPUTES][] = $disputeData[$id];

                    $totalAmount += $disputeData[$id][Entity::AMOUNT];
                }

                $bulkMailData['totalAmount'] = $totalAmount;

                Mail::queue(new DisputeMailer\BulkCreation($bulkMailData));
            }
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
        $input = [];

        foreach ($keys as $key => $value)
        {
            $res = ($row[$key] !== null) ? stringify($row[$key]) : '';
            $res = trim($res);

            $validator = (new Validator);

            $func = 'validateFileColumn' . studly_case($value);

            if (method_exists($validator, $func))
            {
                if ($value === Reason\Entity::NETWORK_CODE)
                {
                    $validator->$func($res, $input);
                }
                else
                {
                    $validator->$func($res);
                }
            }

            $input[$value] = $res;
        }

        return $input;
    }

    /**
     * @param array $dispute
     * @return array
     */

    public function getDisputeDataForMail(array $dispute) : array
    {
        $disputeData = [];

        foreach (self::bulkCreateDisputesMailData as $key)
        {
            $disputeData[$key] = $dispute[$key];
        }

        return $disputeData;
    }
}
