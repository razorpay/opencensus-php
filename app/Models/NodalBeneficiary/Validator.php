<?php

namespace RZP\Models\NodalBeneficiary;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Settlement\Channel;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CHANNEL             => 'required|filled|string|max:8|custom',
        Entity::BENEFICIARY_CODE    => 'sometimes|nullable|string|max:30',
        Entity::REGISTRATION_STATUS => 'required|filled|string|max:40|custom'
    ];

    protected static $editRules = [
        Entity::REGISTRATION_STATUS => 'required|filled|string|max:40',
        Entity::CHANNEL             => 'required|filled|string|max:8|custom',
    ];

    protected static $updateRules = [
        'fund_account_id'         => 'required|integer',
        'status'                  => 'required|filled|string|max:40',
        Entity::CHANNEL           => 'required|string|max:8|custom',
    ];

    protected static $fetchRules = [
        'beneficiary_name'           => 'required|string',
        'beneficiary_ifsc_code'      => 'required|string',
        'beneficiary_account_number' => 'required|string',
        'source_account_number'      => 'required|string',
        'channel'                    => 'required|string'
    ];

    protected static $ftsFundAccountCreateRules = [
        'size'         => 'required|filled|integer',
        'account_type' => 'required|filled|string|in:bank_account',
        'product'      => 'required|filled|string|in:payout,settlement',
    ];

    /**
     * @param string $attribute
     * @param string $value
     * @throws Exception\LogicException
     */
    public function validateRegistrationStatus(string $attribute, string $value)
    {
        $allowedStatus = Status::getAllowedBeneficiaryStatus();

        if (in_array($value, $allowedStatus, true) !== true)
        {
            throw new Exception\LogicException(
                'Invalid beneficiary registration status ' .
                'Status : ' . $value
            );
        }
    }

    /**
     * @param string $newStatus
     * @param string $oldStatus
     * @throws Exception\LogicException
     */
    public function validateNewRegistrationStatus(string $newStatus, string $oldStatus)
    {
        $allowedStatus = Status::getAllowedBeneficiaryStatus();

        if (in_array($newStatus, $allowedStatus, true) !== true)
        {
            throw new Exception\LogicException(
                'Invalid beneficiary registration status ' . $newStatus
            );
        }

        $allowedTransition = Status::getAllowedStatusChange();

        $allowedNewStatus = $allowedTransition[$oldStatus];

        if (in_array($newStatus, $allowedNewStatus, true) !== true)
        {
            throw new Exception\LogicException(
                'Invalid beneficiary registration status transition' .
                         'Old status : ' . $oldStatus.
                         'New status : ' . $newStatus
            );
        }
    }

    /**
     * @param string $attribute
     * @param string $value
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateChannel(string $attribute, string $value)
    {
        $channels = [Channel::YESBANK, Channel::ICICI2];

        if (in_array($value, $channels, true) !== true)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel value : ' . $value);
        }
    }

    /**
     * @param $nodalBeneficiary
     * @param $bankAccountId
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateBankAccount(Entity $nodalBeneficiary, string $bankAccountId)
    {
        if ($nodalBeneficiary == null)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid bankAccount id : '. $bankAccountId);
        }
    }

    /**
     * @param $nodalBeneficiary
     * @param $cardId
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateCard(Entity $nodalBeneficiary, string $cardId)
    {
        if ($nodalBeneficiary == null)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid card id : '. $cardId);
        }
    }
}
