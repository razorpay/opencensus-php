<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;
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
        bool $allowDuplicate = false,
        string $batchId = null): Entity
    {
        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST, ['input' => $input]);

        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->contact->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY],
                $merchant->getId(),
                $batchId);

            if ($result !== null)
            {
                return $result;
            }
        }

        if ($allowDuplicate === true)
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

        if (empty($batchId) === false)
        {
            $contact->setBatchId($batchId);
        }

        $this->setTypeIfApplicable($contact, $input);

        $this->repo->saveOrFail($contact);

        return $contact;
    }

    public function update(Entity $contact, array $input): Entity
    {
        $this->trace->info(
            TraceCode::CONTACT_UPDATE_REQUEST,
            [
                'id'    => $contact->getId(),
                'input' => $input,
            ]);

        $contact->edit($input);

        $this->setTypeIfApplicable($contact, $input);

        $this->repo->saveOrFail($contact);

        return $contact;
    }

    public function delete(Entity $contact)
    {
        $this->trace->info(TraceCode::CONTACT_DELETE_REQUEST, ['id' => $contact->getId()]);

        return $this->repo->deleteOrFail($contact);
    }

    protected function setTypeIfApplicable(Entity $contact, array $input)
    {
        $type = $input[Entity::TYPE] ?? null;

        if ($type === null)
        {
            return;
        }

        (new Type)->setTypeForContact($contact, $type);
    }

    public function processEntryForContact(
        array $entry,
        string $batchId)
    {
        $contact = $entry[ContactBatchHelper::CONTACT];

        $contactId = (isset($contact[ContactBatchHelper::ID]) === true) ? $contact[ContactBatchHelper::ID] : null;

        if (empty($contactId) === false)
        {
            return $this->repo->contact->findByPublicIdAndMerchant($contactId, $this->merchant);
        }

        $input = ContactBatchHelper::getContactInput($entry);

        $contact = $this->create($input, $this->merchant, true, $batchId);

        return $contact;
    }

    public function fetch($id, $merchant, $input = [])
    {
        $contact =  $this->repo->contact->findByPublicIdAndMerchant($id, $merchant, $input);

        return $contact;
    }
}
