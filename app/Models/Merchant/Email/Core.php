<?php

namespace RZP\Models\Merchant\Email;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{

    /**
     *  fetch a merchant's all type of emails
     *
     * @param Merchant\Entity $merchant
     *
     * @return PublicCollection
     */
    public function fetchAllEmails(Merchant\Entity $merchant): PublicCollection
    {
        $emails = $this->repo->merchant_email->getEmailByMerchantId($merchant->getId());

        return $emails;
    }

    /**
     *  Delete a merchant's single type of emails from databases return if it is successful or not
     *
     * @param Merchant\Entity $merchant
     * @param string          $type
     *
     * @return bool
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function deleteEmails(Merchant\Entity $merchant, string $type): bool
    {
        $emails = $this->fetchEmailsByType($merchant, $type);

        $deletedItem = $this->repo->delete($emails);

        return $deletedItem;

    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $data
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function deleteExistingEntities(Merchant\Entity $merchant, array $data)
    {
        $emailType = $data['type'];

        $emails = $this->repo->merchant_email->getEmailByType($emailType, $merchant->getId());

        if (empty($emails) === false)
        {
            $this->deleteEmails($merchant, $emailType);
        }
    }

    /**
     * Fetch a merchant's particular type of emails
     *
     * @param Merchant\Entity $merchant
     * @param string          $type
     *
     * @return Entity
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchEmailsByType(Merchant\Entity $merchant, string $type): Entity
    {
        (new Validator)->validateType(Entity::TYPE, $type);

        $emails = $this->repo->merchant_email->getEmailByType($type, $merchant->getId());

        if ($emails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_DOES_NOT_EXIST);
        }

        return $emails;
    }

    /**
     * Edit merchant email if already exists else create one
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
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

    public function addEmail($merchantId, $type, $emailToBeAdded)
    {
        $emailEntity = $this->repo->merchant_email->getEmailByType($type, $merchantId);

        if (empty($emailEntity) === false)
        {
            $emails = $emailEntity->getEmail();

            if (str_contains($emails, $emailToBeAdded) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS);
            }
            $emailInput = [
                'email'         => sprintf('%s,%s', $emails, $emailToBeAdded),
                Entity::TYPE    => $type,
            ];

            $this->edit($emailEntity, $emailInput);
        }
        else
        {
            $emailInput = [
                'email'         => $emailToBeAdded,
                Entity::TYPE    => $type,
            ];

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->create($merchant, $emailInput);
        }
    }

    public function removeEmail($merchantId, $type, $emailToBeRemoved)
    {
        $emailEntity = $this->repo->merchant_email->getEmailByType($type, $merchantId);

        $emails = $emailEntity->getEmail();

        if (str_contains($emails, $emailToBeRemoved) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_TO_BE_REMOVED_NOT_PRESENT);
        }

        $emailArr = explode(',', $emails);

        $emailArr = array_filter($emailArr, function($email) use ($emailToBeRemoved) {
            return $email !== $emailToBeRemoved;
        });

        $emailInput = [
            'email'         => join(',', $emailArr),
            Entity::TYPE    => $type,
        ];

        $this->edit($emailEntity, $emailInput);
    }

    /**
     * edit merchant email entity based on input
     *
     * @param Entity $email
     * @param array  $input
     *
     * @return Entity
     */
    protected function edit(Entity $email, array $input): Entity
    {
        $email->edit($input);

        $this->repo->saveOrFail($email);

        return $email;
    }

    /**
     * create merchant email entity based on input
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    protected function create(Merchant\Entity $merchant, array $input): Entity
    {
        (new Validator)->validateInput('create', $input);

        $this->trace->info(
            TraceCode::MERCHANT_EMAIL_ADD_REQUEST,
            [
                'input'       => $input
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

    /**
     * @param array $merchantIds
     * @param array $types
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchEmailByMerchantIdsAndTypes(array $merchantIds, array $types): array
    {
        (new Validator)->validateTypes($types);

        $emails = $this->repo->merchant_email->getEmailsByMerchantIdsAndTypes($merchantIds, $types)->toArray();

        return $emails;
    }
}
