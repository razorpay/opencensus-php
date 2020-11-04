<?php
namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TICKET_ID       => 'required|string',
        Entity::TYPE            => 'required|string',
        Entity::TICKET_DETAILS  => 'required|string',
        Entity::MERCHANT_ID     => 'required|string|alpha_num'
    ];

    protected static $fetchCustomerTicketsRules = [
        Entity::CUSTOMER_EMAIL  => 'required|email',
        'otp'                   => 'required',
        'count'                 => 'sometimes'
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid type name: ' . $type);
        }
    }

    public function validateCustomerFreshDeskTicketIdFromMerchantNotes($id)
    {
        $idRegex = '/^.*[0-9]+.*$/';

        $validId = (preg_match($idRegex, $id) === 1);

        if ($validId === false)
        {
            throw new Exception\BadRequestValidationFailureException('The id format is invalid.', 'id');
        }
    }
}
