<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    const MESSAGE        = 'message';

    const EMAIL_SENT     = 'Email Sent';

    const FILE_PATH      = 'file_path';

    const FILE_GENERATED = 'File Generated';

    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->processStatementForAccount($input);

        return $response;
    }

    public function generateAccountStatement(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_GENERATE,
                           ['input' => $input]);

        (new Validator)->setStrictFalse()->validateInput(Validator::ACCOUNT_STATEMENT_GENERATE, $input);

        $statementAccessUrl = $this->core()->generateBankAccountStatement($input);

        $sendEmail = filter_var($input[Entity::SEND_EMAIL], FILTER_VALIDATE_BOOLEAN);

        if ($sendEmail === true)
        {
            $this->core()->sendBankAccountStatementEmail($input, $statementAccessUrl);

            return [self::MESSAGE => self::EMAIL_SENT];
        }

        return [self::MESSAGE => self::FILE_GENERATED, self::FILE_PATH => $statementAccessUrl];
    }
}
