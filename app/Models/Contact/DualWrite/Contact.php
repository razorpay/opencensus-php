<?php

namespace RZP\Models\Contact\DualWrite;

use App;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Contact\Entity;

class Contact extends Base
{
    protected $columnsToUnset = [];

    public function dualWriteRxContact($input)
    {
        $contact = $this->getAPIContactFromInput($input);

        /** @var Entity $apiContact */
        $apiContact = $this->repo->contact->find($contact->getId());

        if (empty($apiContact) === false)
        {
            $contact = $apiContact->setRawAttributes($contact->getAttributes());
        }

        $contact->setIgnoreRelationsForRxDualWrite();

        $this->repo->contact->saveOrFail($contact);

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::RX_CONTACT_DUAL_WRITE_COMPLETE,
            [
                'contact_id' => $input[Entity::ID],
                'timestamp' => $timestamp
            ]
        );

        return $contact;
    }

    public function getAPIContactFromInput($input, bool $sync = false)
    {
        // converts the stdClass object into associative array.
        $this->attributes = $input;

        $this->processModifications();

        $contact = new Entity;

        $contact->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $contact->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $contact->timestamps = false;

        return $contact;
    }
}
