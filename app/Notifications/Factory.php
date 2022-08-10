<?php


namespace RZP\Notifications;

use RZP\Exception;

class Factory
{
    public static function getInstance(string $channel, string $event, string $namespace, array $args,$files)
    {
        switch ($channel)
        {
            case Channel::WHATSAPP:
                $class = self::getWhatsappService($namespace);
                return new $class($event, $args);
            case Channel::SMS:
                $class = self::getSmsService($namespace);
                return new $class($event, $args);
            case Channel::EMAIL:
                $class = self::getEmailService($namespace);
                return new $class($event, $args,$files);
            default:
                throw new Exception\LogicException('invalid channel for notification: '. $channel);
        }
    }

    private static function getEmailService(string $namespace)
    {
        $class = $namespace . '\\' . 'EmailNotificationService';

        if (class_exists($class) === true)
        {
            return $class;
        }
        throw new Exception\LogicException($class . ' is not a valid class');
    }

    private static function getWhatsappService(string $namespace)
    {
        $class = $namespace . '\\' . 'WhatsappNotificationService';

        if (class_exists($class) === true)
        {
            return $class;
        }
        throw new Exception\LogicException($class . ' is not a valid class');
    }

    private static function getSmsService(string $namespace)
    {
        $class = $namespace . '\\' . 'SmsNotificationService';

        if (class_exists($class) === true)
        {
            return $class;
        }
        throw new Exception\LogicException($class . ' is not a valid class');
    }
}
