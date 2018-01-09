<?php

namespace RZP\Models\Admin;

use Cache;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\AdminFetch;
use RZP\Models\GeoIP\Service as GeoIP;

class Service extends Base\Service
{
    public function getAllEntities($input)
    {
        $fields = AdminFetch::fields();
        $entities = AdminFetch::entities();

        // Fetching all entities and fill them with null
        $allEntities = array_fill_keys(Entity::getAllEntities(), null);

        $mergedEntities = array_merge($allEntities, $entities);

        return [
            'version'   => 1,
            'fields'    => $fields,
            'entities'  => $mergedEntities
        ];
    }

    public function fetchEntityById(string $entity, string $id, array $input = []): array
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id, $input);

        return $entity->toArrayAdmin();
    }

    public function fetchTerminalEntityByIdWithFlag($entity, $id, $subMerchantFlag = false)
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id);

        return $entity->toArrayAdmin($subMerchantFlag);
    }

    protected function fetchEntityByNameAndId(
        string $entity,
        string $id,
        array $input = []): Base\PublicEntity
    {
        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $entityObject = new $entityClass;

        if ($entityObject->getIncrementing() === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        $entity = $this->repo->$entity->findOrFailByPublicIdWithParams($id, $input);

        return $entity;
    }

    public function fetchMultipleEntities($entity, $input)
    {
        Entity::validateEntityOrFailPublic($entity);

        $merchantId = $input[Common::MERCHANT_ID] ?? null;

        if ($merchantId !== null)
        {
            Merchant\Entity::verifyIdAndStripSign($merchantId);

            unset($input[Common::MERCHANT_ID]);
        }

        $entities = $this->repo->$entity->fetch($input, $merchantId);

        return $entities->toArrayAdmin();
    }

    public function sendTestNewsletter($input)
    {
        (new Validator)->validateInput('send_test_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setTestEmail($input['email']);

        return $mailer->send();
    }

    public function sendNewsletter($input)
    {
        (new Validator)->validateInput('send_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setRecipient($input['lists']);

        return $mailer->send();
    }

    public function setConfigKeys(array $input): array
    {
        (new Validator)->validateInput('set_config_keys', $input);

        $result = [];

        foreach ($input as $key => $value)
        {
            $result[] = $this->setConfigKey($key, $value);
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $newValue
     *
     * @return array
     */
    protected function setConfigKey(string $key, $newValue): array
    {
        $oldValue = Cache::get($key);

        Cache::forever($key, $newValue);

        $data = [
            'key'       => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ];

        if (ConfigKey::isSensitive($key) === false)
        {
            $this->trace->info(TraceCode::REDIS_KEY_SET, $data);
        }

        return $data;
    }

    public function getConfigKeys(): array
    {
        $result = [];

        foreach (ConfigKey::PUBLIC_KEYS as $key)
        {
            $result[$key] = Cache::get($key);
        }

        return $result;
    }

    public function generateScorecard(array $input)
    {
        $data = (new Scorecard)->generateScorecard($input);

        return $data;
    }

    public function processMailgunCallback($type, $input)
    {
        $validator = new Validator;

        $validator->setStrictFalse();

        $validator->validateInput('mailgun_webhook', $input);

        return (new Mailgun)->processCallback($type, $input);
    }

    public function updateTaxColumnValue(string $entity, int $limit = 10000)
    {
        if (in_array($entity, [Entity::PAYMENT, Entity::TRANSACTION]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid entity: ' . $entity);
        }

        $count = $this->repo->$entity->updateTax($limit);

        return ['count' => $count];
    }

    public function updateGeoIps(array $input)
    {
        return (new GeoIP)->updateGeoIps($input);
    }
}
