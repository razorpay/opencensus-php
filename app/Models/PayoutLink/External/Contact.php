<?php

namespace RZP\Models\PayoutLink\External;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Core as ContactCore;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Contact
 * This class will be used to interact with the Contacts Module.
 * Ideally these should be API calls, but as they are in the same repo, we will be making direct function calls
 * When this module moves out, we will replace function calls with API calls
 */

class Contact
{
    protected $repo;

    public function __construct()
    {
        $this->repo = App::getFacadeRoot()['repo'];
    }

    /**
     * Calls the Contact Core, to create the contact and return the Contact Entity
     *
     * @param array $contactDetails
     * @param MerchantEntity $merchant
     * @return ContactEntity
     * @throws BadRequestException
     */
    public function processContact(array $contactDetails, MerchantEntity $merchant): ContactEntity
    {
        $contactId = array_pull($contactDetails, ContactEntity::ID);

        if (empty($contactId) === false)
        {
            $contact = $this->repo->contact->findByPublicIdAndMerchant($contactId, $merchant);

            if ((empty($contact->getEmail()) === true) and
                (empty($contact->getContact()) === true))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_CONTACT_ID_EMAIL_AND_PHONE_NUMBER_MISSING,
                                              null,
                                              [
                                                  'merchant_id' => $merchant->getPublicId(),
                                                  'contact_id'  => $contact->getPublicId()
                                              ]);
            }
        }
        else
        {

            if (!is_null($contactId) and $contactId === "")
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CONTACT_ID,
                                              null,
                                              [
                                                  'merchant_id' => $merchant->getPublicId(),
                                                  'contact_id'  => $contactId
                                              ]);

            }
            if ((empty($contactDetails[ContactEntity::EMAIL]) === true) and
                (empty($contactDetails[ContactEntity::CONTACT]) === true))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED,
                                              null,
                                              [
                                                  'merchant_id'     => $merchant->getPublicId(),
                                                  'contact_details' => $contactDetails
                                              ]);
            }

            $contact = (new ContactCore())->create($contactDetails, $merchant);
        }

        return $contact;
    }
}
