<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY    => 'filled|integer|min:1|max:10000',
        Entity::ITEM_ID     => 'required_without:item|public_id',
        Entity::ITEM        => 'required_without:item_id|array',
    ];

    public function validateDelete()
    {
        $addon = $this->entity;

        if ($addon->hasInvoice() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADDON_DELETE_NOT_ALLOWED,
                null,
                [
                    Entity::ID         => $addon->getId(),
                    Entity::INVOICE_ID => $addon->getInvoiceId(),
                ]);
        }
    }
}
