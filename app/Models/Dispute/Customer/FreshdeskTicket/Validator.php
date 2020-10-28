<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Dispute;
use RZP\Models\Payment\Entity as PaymentEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::REASON_CODE    => 'required|string',
        Entity::PAYMENT_ID     => 'required|string|size:18|custom',
        Entity::TICKET_ID      => 'required|int',
        Entity::CUSTOMER_EMAIL => 'required|email',
        Entity::CUSTOMER_NAME  => 'required|string',
        Entity::SUBCATEGORY    => 'required|string|custom',
    ];

    protected static $createValidators = [
        'subcategory_reason_code_combination',
    ];

    public function validatePaymentId(string $attribute, string $value)
    {
        $ex = new Exception\BadRequestValidationFailureException(
            'Not a valid ' . $attribute . ': ' . $value
        );

        if ((starts_with($value, 'pay_') === false) ||
            (PaymentEntity::stripSignWithoutValidation($value) === false) ||
            (PaymentEntity::verifyUniqueId($value, false) === false))
        {
            throw $ex;
        }
    }

    public function validateSubcategoryReasonCodeCombination(array $input)
    {
        $subcategory = $input['subcategory'];

        $reasonCode = $input['reason_code'];

        if (ReasonCode::isValidReasonCode($subcategory, $reasonCode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid reason_code: ' . $reasonCode . ' for subcategory: ' . $subcategory
            );
        }
    }

    protected function validateSubcategory(string $attribute, string $value)
    {
        if (Subcategory::isValidSubcategory($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid ' . $attribute . ': ' . $value
            );
        }
    }
}
