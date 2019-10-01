<?php

namespace RZP\Models\Dispute;

use Request;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Dispute\File;
use RZP\Mail\Dispute as DisputeMailer;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    use FileHandlerTrait;

    const bulkDisputeCreateFileName = 'bulk_disputes_create_status';
    const bulkDisputeEditFileName   = 'bulk_disputes_edit_status';

    const bulkCreateDisputesColumns = [
        'payment_id',
        Entity::GATEWAY_DISPUTE_ID,
        Entity::GATEWAY_DISPUTE_STATUS,
        Entity::PHASE,
        Entity::RAISED_ON,
        Entity::EXPIRES_ON,
        Entity::REASON_ID,
        Entity::AMOUNT,
        Entity::MERCHANT_EMAILS,
        Entity::SKIP_EMAIL,
    ];

    const bulkEditDisputesColumns = [
        'dispute_id',
        Entity::GATEWAY_DISPUTE_STATUS,
        Entity::STATUS,
        Entity::SKIP_DEDUCTION,
        Entity::COMMENTS,
    ];

    public function create(array $input, string $paymentId): array
    {
        $payment = $this->repo->payment->findByPublicId($paymentId);

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

    public function getDisputeDataForMail(array $dispute) : array
    {
        $endDate = Carbon::createFromTimestamp($dispute[Entity::EXPIRES_ON], Timezone::IST);

        $daysLeft = $endDate->diffInDays(Carbon::now(Timezone::IST));

        // define array and loop to fetch
        $disputeData[Entity::ID] = $dispute[Entity::ID];
        $disputeData[Entity::PAYMENT_ID] = $dispute[Entity::PAYMENT_ID];
        $disputeData[Entity::MERCHANT_ID] = $dispute[Entity::MERCHANT_ID];
        $disputeData[Entity::GATEWAY_DISPUTE_ID] = $dispute[Entity::GATEWAY_DISPUTE_ID];
        $disputeData[Entity::AMOUNT] = $dispute[Entity::AMOUNT];
        $disputeData[Entity::PHASE] = $dispute[Entity::PHASE];
//        fetch reason_description, not available in admin array
//        $disputeData[Entity::REASON_DESCRIPTION] = $dispute[Entity::REASON_DESCRIPTION];
        $disputeData[Entity::RESPOND_BY] = $dispute[Entity::RESPOND_BY];
        $disputeData['remainingDays'] = $daysLeft;

        return $disputeData;
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

//               Move this to core?
                Mail::queue(new DisputeMailer\BulkCreation($bulkMailData));
            }
        }
    }

    public function bulkCreate(array $input)
    {
        (new Validator)->validateBulkDisputeRequest($input);

        $disputeFileService = new File\Service();

        $data = $disputeFileService->getFileData($input['file']);

        $orderKeys = $data[0];

        if ($this->arrayEqual($orderKeys, self::bulkCreateDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputFileData = $mailData = $merchantData = $disputeData = [];

        $outputKeys = self::bulkCreateDisputesColumns;
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

                // skipping email for each creation
                $input[Entity::SKIP_EMAIL] = true;

                $paymentId = $input['payment_id'];

                unset($input['payment_id']);

                $disputeEntity = $this->create($input, $paymentId);

                if ($skipMails === false)
                {
                    $emails = $input[Entity::MERCHANT_EMAILS] ?? $this->core()->getMerchantEmailsForDispute();

                    if (array_key_exists(Entity::MERCHANT_ID, $merchantData) === false)
                    {
                        $merchant = $this->repo->merchant->find($disputeEntity[Entity::MERCHANT_ID]);

                        $merchantData[$disputeEntity[Entity::MERCHANT_ID]] = $merchant->getName();
                    }

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

        return $url;
    }

    public function bulkUpdate(array $input)
    {
        (new Validator)->validateBulkDisputeRequest($input);

        $disputeFileService = new File\Service();

        $data = $disputeFileService->getFileData($input['file']);

        $orderKeys = $data[0];

        if ($this->arrayEqual($orderKeys, self::bulkEditDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputFileData = [];
        $outputKeys = self::bulkEditDisputesColumns;
        $outputKeys[] = 'errors';

        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $disputeId = $input['dispute_id'];

                unset($input['dispute_id']);

                if ($input[Entity::STATUS] !== Status::LOST)
                {
                    unset($input[Entity::SKIP_DEDUCTION]);
                    unset($input[Entity::COMMENTS]);
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

        return $url;
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

    // Checking if values are same in both arrays irrespective of order
    public function arrayEqual($a, $b)
    {
        return (
            is_array($a) and
            is_array($b) and
            count($a) == count($b) and
            array_diff($a, $b) === array_diff($b, $a)
        );
    }

    // Need to add field based validations
    private function convertFileRowToMap(array $row, array $keys)
    {
        $input = [];

        foreach ($keys as $key=>$value)
        {
            $res = $row[$key];

            switch ($value)
            {
                case Entity::AMOUNT:
                case Entity::GATEWAY_DISPUTE_ID:
                    $res = intval($res);
                    break;

                case Entity::RAISED_ON:
                case Entity::EXPIRES_ON:
                    //Todo : Asserting timestamp is end of day in expires_on and beginning in raised_on
                    $res = Carbon::createFromFormat('d/m/Y', $res)->setTimezone(Timezone::IST)->getTimestamp();
                    break;

                case Entity::MERCHANT_EMAILS:
                    $mails = array_values(explode (",", $res));
                    $res = $mails;
                    break;

                case Entity::SKIP_EMAIL:
                case Entity::SKIP_DEDUCTION:
                    $res = ($row[$key] === 'Y')? true : false;
                    break;
            }

            $input[$value] = $res;
        }

        return $input;
    }
}
