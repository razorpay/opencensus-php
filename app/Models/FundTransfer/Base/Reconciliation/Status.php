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

        return (in_array($bankStatusCode, $statusCodes, true) === true);
    }
}
