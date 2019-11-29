<?php

namespace RZP\Models\Merchant\Email;

use DB;
use Mail;
use Cache;
use Config;
use Request;
use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * Creates or edits a merchant's  different type of emails and saves in databases array
     *
     * @param string $merchantId
     * @param        $input
     *
     * @return array
     */
    public function createEmails(string $merchantId, $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->upsert($merchant, $input);

        return $emails->toArrayPublic();
    }

    /**
     * Fetch a merchant's  different type of emails from databases as an array
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchEmails(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->fetchAllEmails($merchant);

        return $emails->toArrayPublic();
    }

    /**
     * Fetch a merchant's  single type of emails from databases as an array
     *
     * @param string $merchantId
     * @param string $type
     *
     * @return array
     */
    public function fetchEmailByType(string $merchantId, string $type): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->fetchEmailsByType($merchant, $type);

        return $emails->toArrayPublic();
    }

    /**
     * Delete a merchant's  different type of emails from databases as an array
     *
     * @param $merchantId
     * @param $type
     *
     * @return array
     */
    public function deleteEmailByType($merchantId, $type): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $deleteOperation = $this->core()->deleteEmails($merchant, $type);

        return (array) $deleteOperation;
    }

    /**
     * Fetch emails aggregated on merchant level by types
     *
     * @param array $merchantIds
     * @param array $types
     * @return array
     */
    public function fetchEmailByMerchantIdsAndTypes(array $merchantIds, array $types): array
    {
        $emailsArray = $this->core()->fetchEmailByMerchantIdsAndTypes($merchantIds, $types);

        // For aggregating mails on merchant level by type and exploding emails
        $merchantEmailMap = [];

        foreach ($emailsArray as $emailArray)
        {
            $merchantEmailMap[$emailArray[Entity::MERCHANT_ID]][$emailArray[Entity::TYPE]] = array_map(
                'trim',
                explode(',', $emailArray['email'])
            );
        }

        return $merchantEmailMap;
    }
}
