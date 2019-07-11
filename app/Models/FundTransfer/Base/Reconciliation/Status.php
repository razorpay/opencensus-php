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
     * checks if the given status code and response code are part of the list provided
     * list should be in format
     * statusCode => [
     *  subCode1,
     *  subCode2,
     * ]
     *
     * if sub code list is empty then only status code will be respected
     * else both combination of code and sub code will derive the status
     *
     * @param array  $list
     * @param string $statusCode
     * @param null   $responseCode
     *
     * @return bool
     */
    public static function inStatus(array $list, $statusCode, $responseCode = null): bool
    {
        if (isset($list[$statusCode]) === true)
        {
            $responseCodeList = $list[$statusCode];

            if (empty($responseCodeList) === true)
            {
                return true;
            }

            return (in_array($responseCode, $responseCodeList, true) === true);
        }

        return false;
    }

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

            $bankResponseCode = $entity->getBankResponseCode();

            $status = self::isCriticalStatus($bankStatusCode, $bankResponseCode);
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

    public static function isCriticalStatus($bankStatusCode, $bankResponseCode): bool
    {
        $statusCodes = static::getCriticalErrorStatus();

        $isCriticalError = self::inStatus($statusCodes, $bankStatusCode, $bankResponseCode);

        $isCritical =  ($isCriticalError === true);

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

    public static function getPublicFailureReason($bankStatusCode, $bankResponseCode = null)
    {
        $successfulStatus = static::getSuccessfulStatus();

        $isSuccessful = self::inStatus($successfulStatus, $bankStatusCode, $bankResponseCode);

        if ($isSuccessful === true)
        {
            return null;
        }

        // TODO: Put everything in base. Move it out from child classes

        return 'transfer not completed';
    }
}
