<?php

namespace RZP\Models\Dispute;

use Mail;
use Request;
use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Dispute\File;
use RZP\Models\Dispute\Reason;
use RZP\Mail\Dispute as DisputeMailer;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    const bulkDisputeCreateFileName     = 'bulk_disputes_create_status';
    const bulkDisputeEditFileName       = 'bulk_disputes_edit_status';
    const bulkDisputeCreateDateFormat   = 'd/m/Y H:i:s';
    // DB column limits
    const gatewayDisputeIdMaxLength     = 50;
    const gatewayDisputeStatusMaxLength = 255;

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

    const disputePhases = [
        'chargeback',
        'pre_arbitration',
        'arbitration',
        'retrieval',
        'fraud',
    ];

    const disputeStatus = [
        'open',
        'under_review',
        'lost',
        'won',
        'closed',
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
        $validator = new Validator;

        $validator->validateBulkDisputeRequest($input);

        $file = $input['file'];

        $validator->validateBulkDisputesFile($file);

        $disputeFileService = new File\Service();

        $data = $disputeFileService->getFileData($file);

        $orderKeys = $data[0];

        if ($this->arrayEqual($orderKeys, self::bulkCreateDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputFileData = $mailData = $merchantData = $disputeData = [];

        $outputKeys = $orderKeys;
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

                $disputeEntity = $this->create($input, $paymentId);

                if ($skipMails === false)
                {
                    if (empty($emails))
                    {
                        $merchant = $this->repo->merchant->find($disputeEntity[Entity::MERCHANT_ID]);

                        $emails = $this->core()->getMerchantEmailsForDispute($merchant);
                    }

                    if (array_key_exists(Entity::MERCHANT_ID, $merchantData) === false)
                    {
                        $merchant = $this->repo->merchant->find($disputeEntity[Entity::MERCHANT_ID]);

                        $merchantData[$disputeEntity[Entity::MERCHANT_ID]] = $merchant->getName();
                    }

                    $disputeData[$disputeEntity[Entity::ID]] = $this->getDisputeDataForMail($disputeEntity);
                    $disputeData[$disputeEntity[Entity::ID]][Entity::CONTACT] = $contact;

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

        if ($this->arrayEqual($orderKeys, self::bulkEditDisputesColumns) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Column names do not match expected values'
            );
        }

        $outputFileData = [];
        $outputKeys = $orderKeys;
        $outputKeys[] = 'errors';

        $outputFileData[] = $outputKeys;

        for ($i = 1; $i < count($data); $i++)
        {
            $row = $data[$i];

            try
            {
                $input = $this->convertFileRowToMap($row, $orderKeys);

                $disputeId = $input[Entity::ID];

                unset($input[Entity::ID]);

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
                    $res = intval($res);

                    if ($res <= 0)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'amount should be > 0'
                        );
                    }

                    break;

                case Entity::GATEWAY_DISPUTE_ID:
                    $res = stringify($res);

                    if (strlen($res) > self::gatewayDisputeIdMaxLength)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'gateway_dispute_id length exceeds allowed '. self::gatewayDisputeIdMaxLength . ' characters'
                        );
                    }

                    break;

                case Entity::GATEWAY_DISPUTE_STATUS:
                    $res = stringify($res);

                    if (strlen($res) > self::gatewayDisputeStatusMaxLength)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'gateway_dispute_status length exceeds allowed '. self::gatewayDisputeStatusMaxLength . ' characters'
                        );
                    }

                    break;

                case Reason\Entity::NETWORK_CODE:
                    $res = stringify($res);

                    if (empty($res))
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

                    $network = (new Reason\Validator())->validateNetworkWithoutCaseSensitivity($networkCode[0]);

                    $input[Reason\Entity::NETWORK] = $network;

                    $res = $networkCode[1];

                    break;

                case Reason\Entity::REASON_CODE:
                    $res = stringify($res);
                    $res = trim($res);

                    if (empty($res))
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

                    break;

                case Entity::PHASE:
                    $res = stringify($res);

                    if (in_array($res, self::disputePhases, true) === false)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'Invalid phase. Phase should be one of '. implode(", ", self::disputePhases)
                        );
                    }

                    break;

                case Entity::STATUS:
                    $res = stringify($res);

                    if (in_array($res, self::disputeStatus, true) === false)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'Invalid status. status should be one of '. implode(", ", self::disputeStatus)
                        );
                    }

                    break;

                case Entity::RAISED_ON:
                    if (empty($res))
                    {
                        $res = Carbon::now(Timezone::IST)->format('d/m/Y');
                    }

                    // Creation at beginning of the day IST
                    $res .= ' 00:00:00';

                    try
                    {
                        $res = Carbon::createFromFormat(self::bulkDisputeCreateDateFormat, $res, Timezone::IST)->getTimestamp();
                    }
                    catch (\Exception $ex)
                    {
                        // Because default message thrown is incomprehensible
                        throw new Exception\BadRequestValidationFailureException(
                            'Invalid raised_on date argument. Please provide in d/m/Y format'
                        );
                    }

                    $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

                    if ($currentTime < $res)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'raised_on day cannot be greater than current day'
                        );
                    }

                    break;

                case Entity::EXPIRES_ON:
                    if (empty($res))
                    {
                        $res = Carbon::now(Timezone::IST)->addDays(9)->format('d/m/Y');
                    }

                    // Expires at the end of the day IST
                    $res .= ' 23:59:59';

                    try
                    {
                        $res = Carbon::createFromFormat(self::bulkDisputeCreateDateFormat, $res, Timezone::IST)->getTimestamp();
                    }
                    catch (\Exception $ex)
                    {
                        // Because default message thrown is incomprehensible
                        throw new Exception\BadRequestValidationFailureException(
                            'Invalid expires_on date argument. Please provide in d/m/Y format'
                        );
                    }

                    $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

                    if ($currentTime >= $res)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'expires_on time cannot be less than or equal to current time'
                        );
                    }

                    break;

                case Entity::MERCHANT_EMAILS:
                    if (empty($res) === false)
                    {
                        $mails = array_map('trim', explode(',', $res));

                        (new Validator)->validateEmails($mails);

                        $res = $mails;
                    }

                    break;

                case Entity::SKIP_EMAIL:
                case Entity::SKIP_DEDUCTION:
                    switch ($res)
                    {
                        case 'Y':
                        case 'y':
                            $res = true;
                            break;

                        case 'N':
                        case 'n':
                            $res = false;
                            break;

                        default:
                            throw new Exception\BadRequestValidationFailureException(
                                $value . ' field should be Y/N'
                            );
                    }

                    break;

                case Entity::CONTACT:
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

                    break;
            }

            $input[$value] = $res;
        }

        return $input;
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
}
