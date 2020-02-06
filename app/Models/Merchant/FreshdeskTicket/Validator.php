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

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid type name: ' . $type);
        }
    }
}
