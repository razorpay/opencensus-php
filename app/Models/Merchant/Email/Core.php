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
     */
    protected function create(Merchant\Entity $merchant, array $input): Entity
    {
        (new Validator)->validateInput('create', $input);

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

        $newEmail->merchant()->associate($merchant);

        //
        // TODO: Send verification email if it's not a non-communication email id
        // and add verification flow
        //

        $this->repo->saveOrFail($newEmail);

        return $newEmail;
    }

    protected function edit(Entity $email, array $input): Entity
    {
        $email->edit($input);

        $email->saveOrFail();

        return $email;
    }

    public function upsert(Merchant\Entity $merchant, array $input): Entity
    {
        (new Validator)->validateInput('edit', $input);

        $email = $this->repo->merchant_email->getEmailByType($input[Entity::TYPE], $merchant->getId());

        if (empty($email) === false)
        {
            $email = $this->edit($email, $input);
        }
        else
        {
            $email = $this->create($merchant, $input);
        }

        return $email;
    }
}
