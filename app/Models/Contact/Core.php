<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

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
        Batch\Entity $batch = null,
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

        $contact = (new Entity)->build($input);

        $contact->merchant()->associate($merchant);

        $batchId ? ($contact->setBatchId($batchId)) : ($contact->batch()->associate($batch));

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

    public function fetch($id, $merchant, $input = [])
    {
        $contact =  $this->repo->contact->findByPublicIdAndMerchant($id, $merchant, $input);

        return $contact;
    }
}
