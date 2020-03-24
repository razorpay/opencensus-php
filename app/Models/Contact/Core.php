<?php

namespace RZP\Models\Contact;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\BatchHelper as ContactBatchHelper;

/**
 * Class Core
 *
 * @package RZP\Models\Contact
 */
class Core extends Base\Core
{
    public function create(
        array $input,
        Merchant\Entity $merchant,
        string $batchId = null,
        bool $createDuplicate = false,
        bool $allowRZPFeesContactCreation = false): Entity
    {
        $traceData = $this->unsetPersonalIdentifiableInformation($input);

        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST, ['input' => $traceData]);

        (new Validator)->validateInput('create', $input);

        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->contact->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY],
                                                                 $merchant->getId(),
                                                                 $batchId);

            if ($result !== null)
            {
                $this->trace->info(TraceCode::CONTACT_ALREADY_EXISTS_WITH_SAME_IDEMPOTENCY_KEY,
                                   [
                                       'input' => $result->toArrayPublic(),
                                       Entity::IDEMPOTENCY_KEY => $input[Entity::IDEMPOTENCY_KEY]
                                   ]);

                return $result;
            }
        }

        if ($createDuplicate === false)
        {
            $contact = $this->repo->contact->getContactWithSimilarDetails($input, $merchant);

            if ($contact !== null)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_CONTACT_FOUND,
                    [
                        Entity::ID         => $contact->getId(),
                        Entity::BATCH_ID   => $batchId,
                    ]);

                return $contact;
            }
        }

        $contact = (new Entity)->build($input);

        $contact->merchant()->associate($merchant);

        if ($allowRZPFeesContactCreation === true)
        {
            (new Type)->setTypeForInternalContact($contact, $input[Entity::TYPE]);
        }
        else
        {
            $this->setTypeIfApplicable($contact, $input);
        }

        if (empty($batchId) === false)
        {
            $contact->setBatchId($batchId);
        }

        $this->repo->saveOrFail($contact);

        $this->trace->info(TraceCode::CONTACT_CREATED,
            [
                Constants\Entity::CONTACT => $contact->getId(),
            ]);

        return $contact;
    }

    public function update(Entity $contact, array $input): Entity
    {
        $traceData = $this->unsetPersonalIdentifiableInformation($input);

        $this->trace->info(
            TraceCode::CONTACT_UPDATE_REQUEST,
            [
                'id'    => $contact->getId(),
                'input' => $traceData,
            ]);

        (new Validator)->validateInput('edit', $input);

        $this->setTypeIfApplicable($contact, $input);

        // Edit has been shifted below setTypeIfApplicable to handle the case where a merchant tries to update
        // a rzp_fees contact's type to some other type.
        $contact->edit($input);

        $this->repo->saveOrFail($contact);

        return $contact;
    }

    public function delete(Entity $contact)
    {
        // If we ever decide to make this public. Will need to make sure that Internal contact cannot be deleted.
        $this->trace->info(TraceCode::CONTACT_DELETE_REQUEST, ['id' => $contact->getId()]);

        return $this->repo->deleteOrFail($contact);
    }

    protected function setTypeIfApplicable(Entity $contact, array $input)
    {
        // If contact type is 'rzp_fees', we won't allow the merchant to create/update the contact
        // If we need to update this, it should be done at the DB level
        if (Type::isInInternal($contact->getType()) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                null,
                [
                    'contact_id' => $contact->getId(),
                    'input'      => $input
                ]);
        }

        $type = $input[Entity::TYPE] ?? null;

        if ($type === null)
        {
            return;
        }

        (new Type)->setTypeForContact($contact, $type);
    }

    public function processEntryForContact(
        array $entry,
        string $batchId,
        bool $createDuplicate)
    {
        $contact = $entry[ContactBatchHelper::CONTACT];

        $contactId = (isset($contact[ContactBatchHelper::ID]) === true) ? $contact[ContactBatchHelper::ID] : null;

        if (empty($contactId) === false)
        {
            return $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);
        }

        $input = ContactBatchHelper::getContactInput($entry);

        $contact = $this->create($input, $this->merchant, $batchId, $createDuplicate);

        return $contact;
    }

    public function fetch($id, $merchant, $input = [])
    {
        $contact =  $this->repo->contact->findByPublicIdAndMerchant($id, $merchant, $input);

        return $contact;
    }

    public function createRZPFeesContact($merchant)
    {
        $this->trace->info(TraceCode::RZP_FEES_CONTACT_CREATE_REQUEST, [
            'merchant_id' => $merchant->getId()
        ]);

        $contactData = [
            'name'  => $this->config['banking_account.razorpayx_fee_details.name'],
            'type'  => Type::RZP_FEES,
        ];

        $contact = $this->create($contactData, $merchant, null, true, true);

        return $contact;
    }

    protected function unsetPersonalIdentifiableInformation(array $input): array
    {
        if (empty($input[Entity::CONTACT]) === false)
        {
            $input[Entity::CONTACT] = str_repeat('*', strlen($input[Entity::CONTACT]));
        }

        if (empty($input[Entity::EMAIL]) === false)
        {
            $input[Entity::EMAIL] = str_repeat('*', strlen($input[Entity::EMAIL]));
        }

        return $input;
    }
}
