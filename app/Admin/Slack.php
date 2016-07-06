<?php

namespace App\Admin;

use Carbon\Carbon;
use App\Api;
use App\MerchantDetails;

class Slack
{
    const ENTITY_PREFIXES = [
        'pay_'  =>  'payment',
        'setl_' =>  'settlement',
        'card_' =>  'card',
        'txn_'  =>  'transaction',
        'rfnd_' =>  'refund',
    ];

    const DIRECT_MESSAGE = 'directmessage';
    const DIRECT_MESSAGE_ERROR = 'This query will only work on public channels';

    const EMAIL_REGEX = "/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/";

    function __construct($message, $user, $channel)
    {
        $response = "Undefined";
        $this->mode = $this->getMode();

        try
        {
            if ($channel === self::DIRECT_MESSAGE)
            {
                throw new \Exception(self::DIRECT_MESSAGE_ERROR);
            }

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
                return $this->decorateEntity($response);
            }
        }

        throw new \Exception("Couldn't find an entity in the query");
    }

    protected function checkEntityWithPrefix($message)
    {
        // First we try to find a entity with a prefix
        foreach (self::ENTITY_PREFIXES as $prefix => $entity)
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
     * @param string $m message
     * @return  string $m Entity type
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

    // Converts timestamps and entity ids to links
    protected function decorateEntity($data)
    {
        // Enhance it to include additional
        $data = $this->enhanceEntity($data);

        $strategies = [
            'isDate'   =>  'formatDate',
            'isId'     =>  'formatId'
        ];

        foreach ($data as $key => $value)
        {
            foreach ($strategies as $checkMethod => $formatMethod)
            {
                if ($this->$checkMethod($key, $value))
                {
                    $data[$key] = $this->$formatMethod($key, $value);
                }
            }
        }

        return $data;
    }

    /**
     * Attaches more data to the entity
     * if possible/needed
     */
    protected function enhanceEntity(array $data)
    {
        if (!isset($data['entity']))
        {
            return $data;
        }

        switch ($data['entity'])
        {
            case 'merchant':
                $id = $data['id'];
                $merchant_details = MerchantDetails\Entity::findorfail($id);
                $data['contact'] = $merchant_details->contact_mobile;
                $data['contact_name'] = $merchant_details->contact_name;
                break;

            default:
                break;
        }

        return $data;
    }

    protected function isDate($key, $value)
    {
        return substr($key, -3) === '_at';
    }

    protected function isId($key, $value)
    {
        // The public_id field should not be linked
        return ((substr($key, -3) === '_id') and ($key !== 'public_id'));
    }

    protected function formatDate($key, $value)
    {
        return Carbon::createFromTimeStamp($value, "Asia/Kolkata")->format('j M Y h:i a');
    }

    protected function formatId($key, $value)
    {
        $entity = substr($key, 0, -3);

        // We want this field to be dropped in this case
        if (($value === null) or ($value === ""))
        {
            return "";
        }

        return $this->getFormattedLinkForSlack($entity, $value);
    }

    protected function fetchPricing($id)
    {
        return (new Service)->fetchPricingPlan($id);
    }

    public static function getFormattedLinkForSlack($entity, $id, $label = null)
    {
        $label = $label ? $label : $id;

        $mode = static::getMode();

        switch ($entity)
        {
            case 'payment':
                $url = url("admin#/app/payments/$mode/$id");
                break;

            case 'merchant':
                $url = url("admin#/app/merchants/${id}/detail");
                break;

            default:
                $url = url("admin#/app/entity/$mode/$entity/$id");
                break;
        }

        // In the format <link|display_text>
        return '<'. $url . '|' . $label.'>';
    }
}
