<?php

namespace Models\Admin;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models;
use Gateway;

class Service extends Base\Service
{
    public function fetchEntityById($entity, $id)
    {
        $entityClass = $this->getEntityClass($entity);

        $id = $entityClass::verifyIdAndSilentlyStripSign($id);

        $repo = $this->getEntityRepository($entity);

        $entity = (new $repo)->findOrFailPublic($id);

        return $entity->toArrayAdmin();
    }

    public function fetchMultipleEntities($entity, $input)
    {
        $repo = $this->getEntityRepository($entity);

        $repo = new $repo;

        $entities = $repo->fetch($input);

        return $entities->toArrayAdmin();
    }

    protected function getEntityNamespace($entity)
    {
        $map = array(
            'refund'            => Models\Payment\Refund::class,
            'iin'               => Models\Card\IIN::class,
            'daily_settlement'  => Models\Settlement\Daily::class,
            'atom'              => Gateway\Atom::class,
            'bank_account'      => Models\Merchant\BankAccount::class,
            'kotak'             => Gateway\Kotak::class,
            'axis_migs'         => Gateway\AxisMigs::class,
            'axis_genius'       => Gateway\AxisGenius::class,
            'paytm'             => Gateway\Paytm::class,
            'mobikwik'          => Gateway\Mobikwik::class,
            'netbanking'        => Gateway\Netbanking\Base::class,
            'billdesk'          => Gateway\Billdesk::class,
            'hdfc'              => Gateway\Hdfc::class,
            'bank_account'      => Models\Merchant\BankAccount::class,
        );

        if (array_key_exists($entity, $map))
        {
            return $map[$entity];
        }

        return 'Models\\'.ucfirst($entity);
    }

    protected function getEntityClass($entity)
    {
        $class = $this->getEntityNamespace($entity) . '\Entity';

        if (class_exists($class) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid entity: ' . $entity);
        }

        return $class;
    }

    protected function getEntityRepository($entity)
    {
        $class = $this->getEntityNamespace($entity) . '\Repository';

        if (class_exists($class) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid repository: ' . $entity);
        }

        return $class;
    }

    public function sendTestNewsletter($input)
    {
        $errors = (new Validator)->validateInput('send_test_newsletter', $input);

        if(empty($errors))
        {
            // Now we send the newsletter
            $mailer = new Newsletter($input['email'],
                [$input['subj_1'], $input['subj_2']], $input['msg'],
                true // Test Email to self
            );

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

        if(empty($errors))
        {
            $mailer = new Newsletter($input['lists'],
                [$input['subj_1'], $input['subj_2']], $input['msg']
            );

            return $mailer->send();
        }
        else
        {
            return $errors;
        }
    }
}
