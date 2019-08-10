<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Email\Entity as MerchantEmail;

class CreateQuboleMerchantEmailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            MerchantEmail::ID,
            MerchantEmail::TYPE,
            MerchantEmail::PHONE,
            MerchantEmail::POLICY,
            MerchantEmail::URL,
            MerchantEmail::MERCHANT_ID,
            MerchantEmail::VERIFIED,
            MerchantEmail::CREATED_AT,
            MerchantEmail::UPDATED_AT,
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_merchant_emails_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MERCHANT_EMAIL;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_merchant_emails_view');
    }
}
