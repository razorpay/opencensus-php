<?php

namespace RZP\Models\Admin;

use RZP\Constants\Entity;
use RZP\Models\Base;
use RZP\Models;
use RZP\Exception;
use RZP\Base\Common;

class Service extends Base\Service
{
    public function fetchEntityById($entity, $id)
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id);

        return $entity->toArrayAdmin();
    }

    public function fetchTerminalEntityByIdWithFlag($entity, $id, $subMerchantFlag = false)
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id);

        return $entity->toArrayAdmin($subMerchantFlag);
    }

    protected function fetchEntityByNameAndId($entity, $id)
    {
        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $entityObject = new $entityClass;

        if ($entityObject->getIncrementing() === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        $entity = $this->repo->$entity->findOrFailPublic($id);

        return $entity;
    }

    public function fetchMultipleEntities($entity, $input)
    {
        Entity::validateEntityOrFailPublic($entity);

        $merchantId = $input[Common::MERCHANT_ID] ?? null;

        unset($input[Common::MERCHANT_ID]);

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

    public function processMailgunCallback($type, $input)
    {
        $validator = new Validator;

        $validator->setStrictFalse();

        $validator->validateInput('mailgun_webhook', $input);

        return (new Mailgun)->processCallback($type, $input);
    }
}
