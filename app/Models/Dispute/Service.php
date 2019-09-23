<?php

namespace RZP\Models\Dispute;

use Request;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Dispute\File;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    // Need different names for create/edit
    static private $fileToReadName = 'dispute_bulk_file';

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

        $outputFileData = [];

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

                $paymentId = $input['payment_id'];

                unset($input['payment_id']);

                $disputeEntity = $this->create($input, $paymentId);

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

        $file_name = 'bulk_disputes_create_output';

        $url = $disputeFileService->generateFile($outputFileData, $file_name);

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

        $file_name = 'bulk_disputes_edit_output';

        $url = $disputeFileService->generateFile($outputFileData, $file_name);

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
