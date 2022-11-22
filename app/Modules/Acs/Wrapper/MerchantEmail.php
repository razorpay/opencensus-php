<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\MerchantEmailComparator;

class MerchantEmail extends Base
{
    protected $accountAsvClient;
    /**
     * @var MerchantEmailComparator
     */
    private $merchantEmailComparator;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();
        $this->merchantEmailComparator = new MerchantEmailComparator();
    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function Delete(MerchantEmailEntity $entity)
    {
        $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
    }

    /**
     * @param MerchantId $merchantID
     * @throws \RZP\Exception\IntegrationException
     */
    function FetchMerchantEmailsFromMerchantId(string $merchantID)
    {
        $fieldMask = new \Google\Protobuf\FieldMask([
                'paths' => ["merchant_email"]
            ]
        );
        $res = $this->accountAsvClient->FetchMerchant($merchantID, $fieldMask);
        $emails = $this->getMerchantEmailEntitiesFromResponse($res);
        return new PublicCollection($emails);
    }

    function FetchAndCompareMerchantEmailsFromMerchantId(string $merchantID, $emailsFromAPI)
    {
        $emails = $this->FetchMerchantEmailsFromMerchantId($merchantID);
        $this->merchantEmailComparator->compareEmails($emailsFromAPI->toArray(), $emails->toArray());
        return $emails;
    }

    private function getMerchantEmailEntitiesFromResponse(\Rzp\Accounts\Account\V1\FetchMerchantResponse $res)
    {
        $merchant_emails = [];
        $emailsFromAsv = $res->getMerchantEmails();
        foreach($emailsFromAsv as $email) {
            $merchant_email = ASVEntityMapper::MapProtoObjectToEntity($email, MerchantEmailEntity::class);
            array_push($merchant_emails, $merchant_email);
        }
        return $merchant_emails;
    }
}
