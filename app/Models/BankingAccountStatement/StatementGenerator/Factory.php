<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator;

use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl\RBLStatementGenerator;

class Factory
{
    public static function getStatementGenerator($accountNumber, $channel)
    {
        switch ($channel)
        {
            case FTAChannel::RBL:
                return new RBLStatementGenerator($accountNumber, $channel);
        }
    }

}
