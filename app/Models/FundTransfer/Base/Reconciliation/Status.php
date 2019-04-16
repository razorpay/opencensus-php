<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use RZP\Models\FundTransfer\Attempt\Entity;

abstract class Status
{
    abstract public static function getSuccessfulStatus(): array;

    abstract public static function getFailureStatus(): array;

    /**
     * Should be implemented to return all critical error which has to be notified.
     * Data returned by this method will be compared against remark column of FTA.
     *
     * @return array
     */
    abstract public static function getCriticalErrorStatus(): array;

    abstract public static function getCriticalErrorRemarks(): array;

    /**
     * Tells whether critical errors are defined for particular channel
     *
     * @return bool
     */
    public static function hasCriticalErrors(): bool
    {
        $errorMessages = static::getCriticalErrorRemarks();

        $statusCodes =  static::getCriticalErrorStatus();

        if ((empty($errorMessages) === true) and (empty($statusCodes) === true))
        {
            return false;
        }

        return true;
    }

    public static function isInternalError(Entity $entity): bool
    {
        return self::isCriticalError($entity);
    }

    public static function isCriticalError(Entity $entity): bool
    {
        $remark = $entity->getRemarks();

        $status = self::isCriticalRemark($remark);

        if ($status === false)
        {
            $bankStatusCode = $entity->getBankStatusCode();

            $status = self::isCriticalStatus($bankStatusCode);
        }

        return (bool) $status;
    }

    public static function isCriticalRemark($remark): bool
    {
        $errorMessages = static::getCriticalErrorRemarks();

        $status = false;

        foreach ($errorMessages as $message)
        {
            $status |= (stripos($remark, $message) !== false);

            if ($status === true)
            {
                break;
            }
        }

        return (bool) $status;
    }

    public static function isCriticalStatus($bankStatusCode): bool
    {
        $statusCodes = static::getCriticalErrorStatus();

        $isCritical =  (in_array($bankStatusCode, $statusCodes, true) === true);

        try
        {
            // If the status code is not present in the constant list then consider it as critical
            if (($isCritical === false) and
                (defined('static::' . strtoupper(preg_replace('/[^a-zA-Z0-9\']/', '_',$bankStatusCode))) === false))
            {
                $isCritical = true;
            }
        }
        catch(\Throwable $exception)
        {
            $isCritical = true;
        }

        return $isCritical;
    }

    public static function getPublicFailureReason($statusCode)
    {
        if (in_array($statusCode, static::getSuccessfulStatus(), true) === true)
        {
            return null;
        }

        // TODO: Put everything in base. Move it out from child classes

        return "transfer not completed";
    }
}
