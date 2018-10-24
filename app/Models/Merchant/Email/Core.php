<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    /**
     * @param  Merchant\Entity $merchant
     * @param  array $input
     *
     * @throws BadRequestException
     */
    public function create(Merchant\Entity $merchant, array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EMAIL_ADD_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId()
            ]);

        // Calling this before the get call below to avoid calling validator
        // explicitly as build method will call it. The following get is to
        // check for duplicates with the same email + type for the given merchant.
        $newEmail = (new Entity)->generateId();

        $newEmail->build($input);

        $email = $this->repo->merchant_email->getByTypeEmailAndMerchantId(
                                                                    $input[Entity::TYPE],
                                                                    $input[Entity::EMAIL],
                                                                    $merchant->getId());

        if (empty($email) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_DUPLICATE_FOR_TYPE);
        }

        $newEmail->merchant()->associate($merchant);

        $this->repo->saveOrFail($newEmail);

        // TODO: Send verification email if it's not a non-communication email id
        // and add verification flow
    }
}
