<?php

namespace RZP\Models\Item;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'required|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'required|size:3|in:INR',
    ];

    protected static $editRules  = [
        Entity::ACTIVE              => 'sometimes|boolean',
        Entity::NAME                => 'sometimes|string|max:512',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];

    public function validateCurrency(string $itemCurrency, string $invoiceCurrency)
    {
        if ($itemCurrency !== $invoiceCurrency)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Currency of all items should be the same as of the invoice.'
            );
        }
    }

    /**
     * Allows edits if:
     * - Editing fields which are editable always
     * - Editing fields when there is no invoice already generated using this item
     *
     * @param Entity $item
     * @param array  $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateEditOperation(Entity $item, array $input = [])
    {
        $fields = array_diff(array_keys($input), Entity::$fieldsAlwaysEditable);

        if ((count($fields) > 0) and
            ($item->lineItems()->count() > 0))
        {
            $this->raiseOperationNotAllowed($item);
        }
    }

    public function validateDeleteOperation(Entity $item)
    {
        if ($item->lineItems()->count() > 0)
        {
            $this->raiseOperationNotAllowed($item);
        }
    }

    protected function raiseOperationNotAllowed(Entity $item)
    {
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ITEM_OPERATION_NOT_ALLOWED,
            null,
            [
                'item_id' => $item->getId(),
            ]);
    }
}
