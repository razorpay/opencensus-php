<?php

namespace RZP\Models\PayoutLink\Clients;

use App;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
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
    protected $trace;

    protected $repo;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];

        $this->repo = App::getFacadeRoot()['repo'];
    }

    /**
     * Calls the Contact Core, to create the contact and return the Contact Entity
     * @param array $contact
     * @param MerchantEntity $merchant
     * @return ContactEntity
     * @throws BadRequestException
     */
    public function processContact(array $contact, MerchantEntity $merchant): ContactEntity
    {
        $contactId = array_pull($contact, 'contact_id');

        if ($contactId !== null)
        {
            $contact = $this->repo->contact->findByIdAndMerchant($contactId, $merchant);
        }
        else
        {
            $this->trace->info(TraceCode::PAYOUT_LINK_PROCESS_CONTACT_REQUEST,
                               $contact);

            try
            {
                $contact = (new ContactCore())->create($contact, $merchant);
            }
            catch(\Exception $e)
            {
                throw new BadRequestException(
                    $e->getMessage(),
                    ErrorCode::BAD_REQUEST_CONTACT_ADD_FAILED,
                    $contact,
                    $e
                );
            }
        }
        return $contact;
    }
}
