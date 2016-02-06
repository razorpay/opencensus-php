<?php

namespace Models\Admin;

use Models\Api;

class Slack
{
    protected static $entityPrefixes = [
        'pay_'  =>  'payment',
        'setl_' =>  'settlement',
        'card_' =>  'card',
        'txn_'  =>  'transaction',
        'rfnd_' =>  'refund',
    ];

    const EMAIL_REGEX = "/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/";

    function __construct($message, $user, $channel)
    {
        $response = "Undefined";
        $this->mode = $this->getMode();

        try
        {
            $entity = $this->getEntity($message);
            $text = $this->getFormattedLinkForSlack($entity['entity'], $entity['id']);

            $response = [$text, $entity];

            // We found something. Lets log it as well
            // the method is in Logger.php
            (new Service)->logSlackQuery($user, $entity, $channel);
        }
        catch (\Exception $e)
        {
            $response = [$e->getMessage(), []];
        }
        finally
        {
            // Send a valid response to Slack
            $this->response = $response;
        }
    }

    public function getResponse()
    {
        return $this->response;
    }

    protected static function getMode()
    {
        if (\App::environment('dev'))
        {
            return 'test';
        }

        return 'live';
    }

    protected function getEntity($message)
    {
        // The order of strategies is important
        $strategies = ['entity_with_prefix', 'email_address', 'apex_entity'];

        foreach ($strategies as $strategy)
        {
            $method = studly_case("check_$strategy");
            $response = $this->$method($message);

            // We are returned an array
            if ($response)
            {
                return $response;
            }
        }

        throw new \Exception("Couldn't find an entity");
    }

    protected function checkEntityWithPrefix($message)
    {
        // First we try to find a entity with a prefix
        foreach (static::$entityPrefixes as $prefix => $entity)
        {
            preg_match("/$prefix([A-Za-z0-9]{14})/", $message, $matches);

            if (isset($matches[1]))
            {
                // First is the entity type, second is the id
                return $this->fetchEntity($entity, $matches[1]);
            }
        }
    }

    protected function checkApexEntity($message)
    {
        // We haven't found anything matching so far
        // Maybe there is an entity id lurking somewhere

        preg_match("/([A-Za-z0-9]{14})/", $message, $matches);

        // We have an entity id, but we don't know which entity
        if (isset($matches[1]))
        {
            $entity = $this->guessEntityFromMessageCode($message);
            return $this->fetchEntity($entity, $matches[1]);
        }
    }

    protected function checkEmailAddress($message)
    {
        $matches = [];

        // Another approach is to search by email address
        preg_match(static::EMAIL_REGEX, $message, $matches);

        if (isset($matches[1]))
        {
            // We have an email address
            $email = $matches[1];
            $params = [
                'count' =>  1,
                'email' =>  $email
            ];

            list(, $merchants) = (new Service)->fetchMultipleEntities($this->mode, 'merchant', $params);

            if ($merchants['count'] === 1)
            {
                $merchant = $merchants['items'][0];
                return $merchant;
            }
            else
            {
                throw new \Exception("No merchant found with that email address");
            }
        }
    }

    /**
     * This checks the first two characters of the message
     * to find a relevant code for the entity
     * @param  [type] $m [description]
     * @return [type]    [description]
     */
    protected function guessEntityFromMessageCode($m)
    {
        $entity = 'merchant';
        $code = substr($m, 0, 2);

        $entityCodeMap = [
            'm '    =>  'merchant',
            'p '    =>  'payment',
            'r '    =>  'refund',
            's '    =>  'settlement',
            't '    =>  'transaction',
            'c '    =>  'card',
            '$ '    =>  'pricing'
        ];

        if (isset($entityCodeMap[$code]))
        {
            return $entityCodeMap[$code];
        }

        return 'merchant';
    }

    protected function fetchEntity($entity, $id)
    {
        $method = studly_case('fetch_'.$entity);
        $error = null;
        if (method_exists($this, $method))
        {
            list($error, $response) = $this->$method($id);
        }
        else
        {
            list($error, $response) = (new Service)
                ->fetchEntityById($this->mode, $entity, $id);
        }

        if ($error)
        {
            throw new \Exception($error[0]);
        }

        return $response;
    }

    protected function fetchPricing($id)
    {
        return (new Service)->fetchPricingPlan($id);
    }

    public static function getFormattedLinkForSlack($entity, $id, $label = null)
    {
        $label = $label ? $label : $id;

        $mode = static::getMode();
        $url = "https://dashboard.razorpay.com/admin#/app/$entity/$mode/$id";

        // In the format <link|display_text>
        return '<'. $url . '|' . $label.'>';
    }
}
