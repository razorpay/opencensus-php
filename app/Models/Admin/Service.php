<?php

namespace RZP\Models\Admin;

use RZP\Constants\Entity;
use RZP\Models\Base;
use RZP\Models;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Gateway;

class Service extends Base\Service
{
    public function fetchEntityById($entity, $id)
    {
        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $id = $entityClass::verifyIdAndSilentlyStripSign($id);

        $repo = Entity::getEntityRepository($entity);

        $entity = (new $repo)->findOrFailPublic($id);

        return $entity->toArrayAdmin();
    }

    public function fetchMultipleEntities($entity, $input)
    {
        Entity::validateEntityOrFailPublic($entity);

        $repo = Entity::getEntityRepository($entity);

        $repo = new $repo;

        $entities = $repo->fetch($input);

        return $entities->toArrayAdmin();
    }

    public function sendTestNewsletter($input)
    {
        $errors = (new Validator)->validateInput('send_test_newsletter', $input);

        if (empty($errors))
        {
            //
            // Now we send the newsletter
            //
            $mailer = new Newsletter(
                $input['subject'],
                $input['msg'],
                $input['template']
            );

            $mailer->setTestEmail($input['email']);

            return $mailer->send();
        }
        else
        {
            return $errors;
        }
    }

    public function sendNewsletter($input)
    {
        $errors = (new Validator)->validateInput('send_newsletter', $input);

        if (empty($errors))
        {
            $mailer = new Newsletter(
                $input['subject'],
                $input['msg'],
                $input['template']
            );

            $mailer->setRecipient($input['lists']);

            return $mailer->send();
        }
        else
        {
            return $errors;
        }
    }
}
