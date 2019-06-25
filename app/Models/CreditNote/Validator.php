<?php

namespace RZP\Models\CreditNote;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity as PublicEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CUSTOMER_ID     => 'required|public_id|size:19',
        Entity::SUBSCRIPTION_ID => 'sometimes|string|size:14|nullable',
        Entity::NAME            => 'required|string|max:255||utf8',
        Entity::DESCRIPTION     => 'sometimes|string|max:2048|utf8',
        Entity::AMOUNT          => 'required|mysql_unsigned_int|min_amount',
        Entity::CURRENCY        => 'required|currency',
    ];

    protected static $preCreateRules = [
        Entity::CUSTOMER_ID     => 'required|public_id|size:19',
        Entity::SUBSCRIPTION_ID => 'sometimes|public_id|size:18|nullable',
        Entity::NAME            => 'required|string|max:255|utf8',
        Entity::DESCRIPTION     => 'sometimes|string|max:2048|utf8',
        Entity::AMOUNT          => 'required|mysql_unsigned_int|min_amount',
        Entity::CURRENCY        => 'required|currency',
    ];

    protected static $applyRules = [
        Entity::ACTION   =>  'required|string|max:20|in:refund',
        Entity::INVOICES => 'required|sequential_array|min:1|custom',
    ];

    protected static $applyItemsRules = [
        Entity::INVOICE_ID =>  'required|public_id|size:18',
        Entity::AMOUNT     => 'required|mysql_unsigned_int|min_amount',
    ];

    public function validateInvoices($attribute, $value)
    {
        $invoices = [];
        foreach ($value as $row)
        {
            $this->validateInput('applyItems', $row);

            $invoices[] = $row[Entity::INVOICE_ID];
        }

        if (count($invoices) !== count(array_unique($invoices)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Duplicate items for invoices');
        }
    }

    public function validateMinAmount(array $input)
    {
        if (empty($input[Entity::AMOUNT]) === false)
        {
            $currency = $this->entity->getCurrency();

            $inputAmount = [
                Entity::AMOUNT   => $input[Entity::AMOUNT],
                Entity::CURRENCY => $currency,
            ];

            $this->validateInputValues('min_amount_check', $inputAmount);
        }
    }
}
