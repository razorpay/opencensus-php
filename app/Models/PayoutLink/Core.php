<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Models\PayoutLink\Clients\Contact;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Contact\Entity as ContactEntity;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        #todo: pl a log here
        array_pull($input, 'XDEBUG_SESSION_START');

        # remove all the contact information from here
        # check if we have to create a contact and create one if required
        # add that info in the final input
        # build the payoutlink and save it
        # return
        # errors thrown
        # all the contact related errors, see if you can just throw them from what ever is returned
        # all the payout links errors, that the DB will create
        # see how the exceptions are thrown too

        $validator = (new Entity())->getValidator();

        $validator->validateInput('compositeCreate', $input);

        $this->processContact($input);

        $payoutLink = (new Entity)->build($input);

        $this->generateAndSetShortUrl($payoutLink);

        $payoutLink->merchant()->associate($this->merchant);

        $payoutLink->saveOrFail();

        return $payoutLink;
    }

    protected function generateAndSetShortUrl(Entity &$payoutLink)
    {
        $shortUrl = 'https://fakeurl.com';

        $payoutLink->setShortUrl($shortUrl);
    }

    protected function processContact(array &$input)
    {
        $contact = array_pull($input, 'contact');

        $contactId = array_pull($contact, 'contact_id');

        if ($contactId === null)
        {
            #todo: pl a log here
            $contactClient = new Clients\Contact();

            try
            {
                $contactEntity = $contactClient->createContact($contact,  $this->merchant);

                $input['contact_id'] = $contactEntity->getId();
            }
            catch(\Exception $e)
            {
                #todo: pl a  log here
                dd($e);
                #todo: pl raise the exception so that this becomes a client error ?
            }
        }
    }
}
