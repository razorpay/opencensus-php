<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Card\Entity as Card;

class CreateQuboleCardsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Card::ID,
            Card::MERCHANT_ID,
            Card::IIN,
            Card::LENGTH,
            Card::NETWORK,
            Card::TYPE,
            Card::ISSUER,
            Card::EMI,
            Card::INTERNATIONAL,
            Card::VAULT,
            Card::TRIVIA,
            Card::COUNTRY,
            Card::GLOBAL_CARD_ID,
            Card::CREATED_AT,
            Card::UPDATED_AT,
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_cards_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CARD;

        DB::statement($statement);
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_cards_view');
    }
}
