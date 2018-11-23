<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Customer\Token\Entity as Token;


class CreateQuboleTokensView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Token::ID,
            Token::CUSTOMER_ID,
            Token::MERCHANT_ID,
            Token::TERMINAL_ID,
            Token::METHOD,
            Token::CARD_ID,
            Token::BANK,
            Token::MAX_AMOUNT,
            Token::WALLET,
            Token::ACCOUNT_NUMBER,
            Token::BENEFICIARY_NAME,
            Token::IFSC,
            Token::AADHAAR_NUMBER,
            Token::AUTH_TYPE,
            Token::RECURRING,
            Token::RECURRING_STATUS,
            Token::RECURRING_FAILURE_REASON,
            Token::CONFIRMED_AT,
            Token::REJECTED_AT,
            Token::INITIATED_AT,
            Token::ACKNOWLEDGED_AT,
            Token::USED_COUNT,
            Token::USED_AT,
            Token::EXPIRED_AT,
            Token::CREATED_AT,
            Token::UPDATED_AT,
            Token::DELETED_AT,
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_tokens_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TOKEN;

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_tokens_view');
    }
}