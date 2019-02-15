<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Contact as ContactModel;

class Contact extends Base
{
    /**
     * @var ContactModel\Core
     */
    protected $core;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->core = new ContactModel\Core;
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $contact = $this->processEntryAndGetEntity($entry);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        $entry[Batch\Header::CONTACT_ID] = $contact->getPublicId();
    }

    public function processEntryAndGetEntity(array & $entry): ContactModel\Entity
    {
        $input = Batch\Helpers\Contact::getContactInput($entry);

        $contact = $this->repo->contact->getContactWithSimilarDetails($input, $this->merchant);

        return $contact ?: $this->core->create($input, $this->merchant);
    }
}
