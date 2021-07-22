<?php


namespace RZP\Models\Dispute\Evidence;


use RZP\Models\Dispute;

class Action
{
    const DRAFT  = "draft";
    const SUBMIT = "submit";
    const ACCEPT = "accept";


    protected static $validActions = [
        self::DRAFT,
        self::SUBMIT,
        self::ACCEPT,
    ];

    protected static $validActionForDisputeStatusMap = [
        self::DRAFT  => [Dispute\Status::OPEN],
        self::SUBMIT => [Dispute\Status::OPEN],
        self::ACCEPT => [Dispute\Status::OPEN],

    ];

    public static function isValidAction(string $action): bool
    {
        return in_array($action, self::$validActions) === true;
    }

    public static function isValidActionForDisputeStatus(string $action, string $disputeStatus): bool
    {
        if (self::isValidAction($action) === false)
        {
            return false;
        }

        return in_array($disputeStatus, self::$validActionForDisputeStatusMap[$action], true) === true;
    }
}