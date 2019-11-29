<?php

namespace RZP\Models\PayoutLink\Clients;

use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\Contact\Core as ContactCore;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class Contact
 * This class will be used to interact with the Contacts Module.
 * Ideally these should be API calls, but as they are in the same repo, we will be making direct function calls
 * When this module moves out, we will replace function calls with API calls
 */
class Contact
{
    public function getContactById(string $contactId): ContactEntity
    {

    }

    /**
     * @param array $input
     * @param MerchantEntity $merchant
     * @return ContactEntity
     * Calls the Contact Core, to create the contact and return the contact entity ...
     */
    public function createContact(array $input, MerchantEntity $merchant): ContactEntity
    {
        $contact = (new ContactCore())->create($input, $merchant);

        return $contact;
    }
}