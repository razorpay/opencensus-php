<?php

namespace RZP\Models\Dispute;

use Mail;
use Request;

use RZP\Exception;
use RZP\Models\{Base, Payment};
use RZP\Models\Dispute\File;
use RZP\Models\Dispute\Reason;
use RZP\Mail\Dispute as DisputeMailer;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    const bulkDisputeCreateFileName     = 'bulk_disputes_create_status';
    const bulkDisputeEditFileName       = 'bulk_disputes_edit_status';

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

    public function sendAggregatedEmails(array $mailData, array $merchantData, array $disputeData)
    {
        foreach ($mailData as $merchantId=>$data)
        {
            foreach ($data as $mailId=>$disputeIds)
            {
                $bulkMailData['merchant']['name'] = $merchantData[$merchantId];
                $bulkMailData['merchant']['email'] = $mailId;
                $bulkMailData['disputes'] = [];
                $totalAmount = 0;

                foreach ($disputeIds as $id)
                {
                    $bulkMailData['disputes'][] = $disputeData[$id];

                    $totalAmount += $disputeData[$id][Entity::AMOUNT];
                }

                $bulkMailData['totalAmount'] = $totalAmount;

                Mail::queue(new DisputeMailer\BulkCreation($bulkMailData));
            }
        }
    }

    public function bulkCreate(array $input)
    {
        $validator = new Validator;

        $validator->validateBulkDisputeRequest($input);

        $file = $input['file'];

        $validator->validateBulkDisputesFile($file);

        $disputeFileService = new File\Service();

        $data = $disputeFileService->getFileData($file);

        $orderKeys = $data[0];

        if ($validator->arrayEqual($orderKeys, self::bulkCreateDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputFileData = $mailData = $merchantData = $disputeData = [];

        $outputKeys   = $orderKeys;
        $outputKeys[] = 'rzp_dispute_id';
        $outputKeys[] = 'errors';

        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $skipMails = $input[Entity::SKIP_EMAIL];
                $contact   = $input[Entity::CONTACT];
                $emails    = $input[Entity::MERCHANT_EMAILS];

                // skipping email for each creation
                $input[Entity::SKIP_EMAIL] = true;

                $paymentId = $input[Entity::PAYMENT_ID];

                $reasonId = (new Reason\Service())->getReasonIdFromAttributes(
                    $input[Reason\Entity::NETWORK], $input[Reason\Entity::NETWORK_CODE], $input[Reason\Entity::REASON_CODE]);

                if (count($reasonId) !== 1)
                {
                    throw new Exception\RecoverableException(
                        'There are no entries/more than 1 entries in DB for the given combination of network_code and reason_code'
                    );
                }

                $input[Entity::REASON_ID] = $reasonId[0];

                unset($input[Entity::CONTACT]);
                unset($input[Entity::PAYMENT_ID]);
                unset($input[Entity::MERCHANT_EMAILS]);
                unset($input[Reason\Entity::NETWORK]);
                unset($input[Reason\Entity::NETWORK_CODE]);
                unset($input[Reason\Entity::REASON_CODE]);

                $payment = $this->repo->payment->findByPublicId($paymentId);

                $merchant = $payment->merchant;

                $disputeEntity = $this->create($input, $paymentId, $payment);

                if ($skipMails === false)
                {
                    if (empty($emails))
                    {
                        $emails = $this->core()->getMerchantEmailsForDispute($merchant);
                    }

                    $emails = array_unique($emails);

                    if (array_key_exists(Entity::MERCHANT_ID, $merchantData) === false)
                    {
                        $merchantData[$disputeEntity[Entity::MERCHANT_ID]] = $merchant->getName();
                    }

                    if (empty($contact))
                    {
                        $contact = $payment[Payment\Entity::CONTACT];

                        if (empty($contact))
                        {
                            $contact = 'N/A';
                        }
                    }

                    $disputeEntity[Entity::CONTACT] = $contact;

                    $disputeData[$disputeEntity[Entity::ID]] = $this->getDisputeDataForMail($disputeEntity);

                    foreach ($emails as $mail)
                    {
                        $mailData[$disputeEntity[Entity::MERCHANT_ID]][$mail][] = $disputeEntity[Entity::ID];
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

        $url = $disputeFileService->generateFile($outputFileData, self::bulkDisputeCreateFileName);

        return [
            'link' => $url,
        ];
    }

    public function bulkUpdate(array $input)
    {
        $validator = new Validator;

        $validator->validateBulkDisputeRequest($input);

        $file = $input['file'];

        $validator->validateBulkDisputesFile($file);

        $disputeFileService = new File\Service();

        $data = $disputeFileService->getFileData($file);

        $orderKeys = $data[0];

        if ($validator->arrayEqual($orderKeys, self::bulkEditDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputKeys   = $orderKeys;
        $outputKeys[] = 'errors';

        $outputFileData   = [];
        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $disputeId = $input[Entity::ID];

                unset($input[Entity::ID]);

                if (empty($input[Entity::GATEWAY_DISPUTE_STATUS]))
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

                $dispute = $this->repo->dispute->findOrFail($disputeId);

                $this->core()->update($dispute, $input);

                $row[] = '';
            }
            catch (\Exception $e)
            {
                $row[] = $e->getMessage();
            }

            $outputFileData[] = $row;
        }

        $url = $disputeFileService->generateFile($outputFileData, self::bulkDisputeEditFileName);

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

    // Validates each column of the file and converts it to necessary format
    private function convertFileRowToMap(array $row, array $keys)
    {
        $input = [];

        foreach ($keys as $key=>$value)
        {
            $res = stringify($row[$key]);
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
