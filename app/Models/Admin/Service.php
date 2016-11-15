<?php

namespace RZP\Models\Admin;

use RZP\Constants\Entity;
use RZP\Models\Base;
use RZP\Models;
use RZP\Exception;

class Service extends Base\Service
{
    public function fetchEntityById($entity, $id)
    {
        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $entityObject = new $entityClass;

        if ($entityObject->getIncrementing() === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        $entity = $this->repo->$entity->findOrFailPublic($id);

        return $entity->toArrayAdmin();
    }

    public function fetchMultipleEntities($entity, $input)
    {
        Entity::validateEntityOrFailPublic($entity);

        $entities = $this->repo->$entity->fetch($input);

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
    
    public function processEmailFailure($input)
    {
        (new Validator)->validateInput('process_email_failure', $input);
        
        return (new Mailgun)->processEmailFailureCheck($input);
    }
}
