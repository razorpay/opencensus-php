<?php

namespace RZP\Models\Dispute;

use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Admin\File;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const OPERATION_MERCHANT_EDIT = 'merchant_edit';

    // Max allowed file size - 30MB (30*1024*1024).
    const MAX_FILE_SIZE = 31457280;

    // DB column limits
    const gatewayDisputeIdMaxLength     = 50;
    const gatewayDisputeStatusMaxLength = 255;

    const bulkDisputeCreateDateFormat   = 'd/m/Y H:i:s';

    const ACCEPTED_EXTENSIONS = [
        FileStore\Format::CSV,
        FileStore\Format::XLS,
        FileStore\Format::XLSX,
    ];

    protected static $createRules = [
        Entity::GATEWAY_DISPUTE_ID     => 'required|alpha_num',
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::PHASE                  => 'required|string|custom',
        Entity::RAISED_ON              => 'required|epoch',
        Entity::EXPIRES_ON             => 'required|epoch',
        Entity::REASON_ID              => 'required|alpha_num|size:14',
        Entity::AMOUNT                 => 'required|integer|min:100',
        Entity::DEDUCT_AT_ONSET        => 'sometimes|boolean',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_EMAILS        => 'sometimes|array',
        Entity::MERCHANT_EMAILS . '.*' => 'filled|email',
        Entity::SKIP_EMAIL             => 'sometimes|boolean',
    ];

    protected static $editRules = [
        Entity::GATEWAY_DISPUTE_STATUS => 'sometimes|string',
        Entity::STATUS                 => 'sometimes|string|custom',
        Entity::ACCEPTED_AMOUNT        => 'sometimes|integer|min:100',
        Entity::EXPIRES_ON             => 'sometimes|epoch',
        Entity::PARENT_ID              => 'sometimes|alpha_num|size:14',
        Entity::SKIP_DEDUCTION         => 'sometimes|boolean',
        Entity::COMMENTS               => 'sometimes|string|min:5|max:255|utf8',
    ];

    protected static $bulkCreateRules = [
        File\Core::FILE => 'required|file',
    ];

    protected static $createValidators = [
        'deduct_onset_for_non_transactional_phases',
    ];

    protected static $editValidators = [
        'non_transactional_disputes_closure',
    ];

    protected static $merchantEditRules = [
        Entity::ACCEPT_DISPUTE         => 'sometimes|boolean',
        Entity::SUBMIT                 => 'sometimes|boolean',
    ];

    protected static $emailRules = [
        Entity::EMAIL => 'required|email',
    ];

    protected function validatePhase(string $attribute, string $value)
    {
        if (Phase::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute phase: ' . $value);
        }
    }

    protected function validateStatus(string $attribute, string $value)
    {
        if (Status::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid dispute status: ' . $value);
        }

        if ($this->entity->isClosed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE);
        }
    }

    public function validatePaymentForDispute(array $input, Payment\Entity $payment)
    {
        if ($payment->isDisputed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE,
                null,
                ['input' => $input, 'payment_id' => $payment->getId()]);
        }

        //
        // This function is called before the build validator
        // Hence, if amount is not set, return from here and let
        // the build validator take care of it
        //
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        if ($payment->getAmount() < $input[Entity::AMOUNT])
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT,
                Entity::AMOUNT,
                ['input' => $input, 'payment_id' => $payment->getId()]);
        }
    }

    public function validateInputBeforeBuild(array $input)
    {
        if (empty($input[Entity::REASON_ID]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'reason_id should be sent in the request to create a dispute.',
                Entity::REASON_ID,
                $input);
        }
    }

    public function validateBulkDisputeRequest(array $input)
    {
        if (empty($input[File\Core::FILE]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'file should be attached in the request to create disputes in bulk',
                File\Core::FILE,
                $input);
        }
    }

    /**
     *  We ensured via $editRules that $input[Entity::ACCEPTED_DISPUTE_AMOUNT] must be positive value.
     *  Here we put an upper limit to value of same.
     *
     * @param int $disputedAmount
     * @param array $input
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAcceptedDisputeAmount(int $disputedAmount, array $input)
    {
        if ($input[Entity::ACCEPTED_AMOUNT] > $disputedAmount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Accepted chargeback amount cannot be greater than disputed amount.',
                Entity::ACCEPTED_AMOUNT,
                $input);
        }
    }

    public function validateDisputeCanBecomeParent()
    {
        if ($this->entity->child !== null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The parent dispute is linked to another dispute entity.',
                Entity::PARENT_ID);
        }
    }

    public function validateForMerchantUpdate(array $input)
    {
        if ($this->entity->getStatus() !== Status::OPEN)
        {
            throw new BadRequestValidationFailureException(
                'Disputes can only be modified when in open status');
        }

        if ((isset($input[Entity::ACCEPT_DISPUTE]) === true) and
            (isset($input[Entity::SUBMIT]) === true))
        {
            throw new BadRequestValidationFailureException(
                'Only one of the fields `accept_dispute` and `submit` can be sent');
        }
    }

    protected function validateNonTransactionalDisputesClosure($input)
    {
        if (isset($input[Entity::STATUS]) === false)
        {
            return;
        }

        if ($this->entity->isNonTransactional() === false)
        {
            return;
        }

        if (in_array($input[Entity::STATUS], Status::getTransactionalStatuses(), true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non-transactional disputes can only be closed.',
                Entity::STATUS,
                $input);
        }
    }

    public function validateDeductOnsetForNonTransactionalPhases(array $input)
    {
        if (empty($input[Entity::DEDUCT_AT_ONSET]) === true)
        {
            return;
        }

        $nonTransactionalPhases = Phase::getNonTransactionalPhases();

        if (in_array($input[Entity::PHASE], $nonTransactionalPhases,true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Deduct at onset cannot be done for disputes in phase ' . $input[Entity::PHASE],
                Entity::DEDUCT_AT_ONSET,
                $input);
        }
    }

    // Checks if values are same in both arrays irrespective of the order
    public function arrayEqual(array $a, array $b) : bool
    {
        return ((count($a) === count($b)) and (array_diff($a, $b) === array_diff($b, $a)));
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension is as expected.
     *
     * @param $file
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkDisputesFile($file)
    {
        if ($file->getSize() > self::MAX_FILE_SIZE)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File Size exceeds max allowed size of 30MB'
            );
        }

        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, self::ACCEPTED_EXTENSIONS, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid File extension. Only '. implode(", ", self::ACCEPTED_EXTENSIONS) . ' file formats are allowed'
            );
        }
    }

    /**
     * Validates if the file size is within the limits and
     * validates if extension is as expected.
     *
     * @param $emails
     * @return void   throws exception for error
     */
    public function validateEmails(array $emails)
    {
        foreach ($emails as $email)
        {
            $this->validateInput('email', [Entity::EMAIL => $email]);
        }
    }

    public function validateFileColumnAmount(string &$res)
    {
        $res = intval($res);

        if ($res <= 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'amount should be > 0'
            );
        }
    }

    public function validateFileColumnGatewayDisputeId(string &$res)
    {
        if (empty($res))
        {
            throw new Exception\BadRequestValidationFailureException(
                'gateway_dispute_id cant be empty'
            );
        }

        if (strlen($res) > self::gatewayDisputeIdMaxLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'gateway_dispute_id length exceeds allowed '. self::gatewayDisputeIdMaxLength . ' characters'
            );
        }
    }

    public function validateFileColumnGatewayDisputeStatus(string &$res)
    {
        if ((empty($res) === false) and (strlen($res) > self::gatewayDisputeStatusMaxLength))
        {
            throw new Exception\BadRequestValidationFailureException(
                'gateway_dispute_status length exceeds allowed '. self::gatewayDisputeStatusMaxLength . ' characters'
            );
        }
    }

    public function validateFileColumnReasonCode(string &$res)
    {
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
    }

    public function validateFileColumnPhase(string &$res)
    {
        if (in_array($res, Phase::list(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid phase. Phase should be one of '. implode(", ", Phase::list())
            );
        }
    }

    public function validateFileColumnStatus(string &$res)
    {
        if (in_array($res, Status::list(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status. Status should be one of '. implode(", ", Status::list())
            );
        }
    }

    public function validateFileColumnNetworkCode(string &$res, array &$input)
    {
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
    }

    public function validateFileColumnRaisedOn(string &$res)
    {
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
    }

    public function validateFileColumnExpiresOn(string &$res)
    {
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
                'Invalid expires_on date. Please provide in d/m/Y format'
            );
        }

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($currentTime >= $res)
        {
            throw new Exception\BadRequestValidationFailureException(
                'expires_on time cannot be less than or equal to current time'
            );
        }
    }

    public function validateFileColumnContact(string &$res)
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
    }

    public function validateFileColumnMerchantEmails(string &$res)
    {
        if (empty($res) === false)
        {
            $mails = array_map('trim', explode(',', $res));

            $this->validateEmails($mails);

            $res = $mails;
        }
    }

    // Convert Y/N to true/false
    public function convertYNToBool(string $res)
    {
        $res = strtoupper($res);

        switch ($res)
        {
            case 'Y':
                return true;

            case 'N':
                return false;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    $res . ' field should be Y/N'
                );
        }
    }

    public function validateFileColumnSkipEmail(string &$res)
    {
        $res = $this->convertYNToBool($res);
    }

    public function validateFileColumnSkipDeduction(string &$res)
    {
        $res = $this->convertYNToBool($res);
    }
}
