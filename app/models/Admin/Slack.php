<?php

namespace Models\Admin;

class Slack
{
    protected static $entityPrefixes = [
        'pay_'  =>  'payment',
        'setl_' =>  'settlement',
        'card_' =>  'card',
        'txn_'  =>  'transaction',
        'rfnd_' =>  'refund',
    ];

    function __construct($message)
    {
        $response = "Undefined";

        try
        {
            list($entity, $id) = $this->getEntityAndId($message);

            $mode = $this->getMode();

            list($error, $response) = (new Service)->fetchEntityById($mode, $entity, $id);

            $response = ['Entity URL', $response];
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

    protected function getMode()
    {
        if (\App::environment('dev'))
        {
            return 'test';
        }

        return 'live';
    }

    protected function getEntityAndId($message)
    {
        // First we try to find a entity with a prefix
        foreach (static::$entityPrefixes as $prefix => $entity)
        {
            preg_match("/$prefix([A-Za-z0-9]{14})/", $message, $matches);

            if (isset($matches[1]))
            {
                // First is the entity type, second is the id
                return [$entity, $matches[1]];
            }
        }

        // We haven't found anything matching so far
        // Maybe there is an entity id lurking somewhere

        preg_match("/([A-Za-z0-9]{14})/", $message, $matches);

        // We have an entity id, but we don't know which entity
        if (isset($matches[1]))
        {
            $entity = $this->guessEntityFromMessage($message);
            return [$entity, $matches[1]];
        }

        throw new \Exception("Couldn't find an entity");
    }

    /**
     * This checks the first two characters of the message
     * to find a relevant code for the entity
     * @param  [type] $m [description]
     * @return [type]    [description]
     */
    protected function guessEntityFromMessage($m)
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
        ];

        if (isset($entityCodeMap[$code]))
        {
            return $entityCodeMap[$code];
        }

        return 'merchant';
    }
}
