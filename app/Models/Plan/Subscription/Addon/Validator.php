<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY    => 'filled|integer|min:1|max:10',
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
                    'addon_id' => $addon->getId(),
                    'invoice_id' => $addon->getInvoiceId(),
                ]);
        }
    }
}
