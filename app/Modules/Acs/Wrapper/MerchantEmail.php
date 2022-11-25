<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Modules\Acs\Comparator\MerchantEmailComparator;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmail extends Base
{
    public $accountAsvClient;
    /**
     * @var MerchantEmailComparator
     */
    private $merchantEmailComparator;

    public $saveApiAsvClient;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();
        $this->merchantEmailComparator = new MerchantEmailComparator();
        $this->saveApiAsvClient = new AsvClient\SaveApiAsvClient();
    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function Delete(MerchantEmailEntity $entity)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'shadow', 'write') === true) {
            try {
                $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'reverse_shadow', 'write') === true) {
            try {
                $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantEmailEntity $entity)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'shadow', 'write') === true) {
            try {
                $this->saveApiAsvClient->SaveEntity($entity->getMerchantId(), $entity->getEntityName(), $entity->toArray());
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->save', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'reverse_shadow', 'write') === true) {
            try {
                $this->saveApiAsvClient->SaveEntity($entity->getMerchantId(), $entity->getEntityName(), $entity->toArray());
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->save', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }

    /**
     * @param string $merchantID
     * @return PublicCollection
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
        foreach ($emailsFromAsv as $email) {
            $merchant_email = ASVEntityMapper::MapProtoObjectToEntity($email, MerchantEmailEntity::class);
            array_push($merchant_emails, $merchant_email);
        }
        return $merchant_emails;
    }
}
