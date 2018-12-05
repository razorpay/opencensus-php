<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

/**
 * Class Core
 *
 * @package RZP\Models\Contact
 */
class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::CONTACT_CREATE_REQUEST, ['input' => $input]);

        $contact = (new Entity)->build($input);

        $contact->merchant()->associate($merchant);

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

        $this->repo->saveOrFail($contact);

        return $contact;
    }

    public function delete(Entity $contact)
    {
        $this->trace->info(TraceCode::CONTACT_DELETE_REQUEST, ['id' => $contact->getId()]);

        return $this->repo->deleteOrFail($contact);
    }
}
