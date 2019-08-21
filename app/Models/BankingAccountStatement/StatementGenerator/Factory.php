<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator;

class Factory
{
    public static function getStatementGenerator($account_number, $channel, $format)
    {
        switch ($channel) {
            case "rbl":
                return new RBLStatementGenerator($account_number, $channel, $format);
        }
    }
}
