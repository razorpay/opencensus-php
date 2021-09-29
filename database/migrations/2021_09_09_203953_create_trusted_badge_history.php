<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\TrustedBadge\TrustedBadgeHistory\Entity as TrustedBadgeHistory;

class CreateTrustedBadgeHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRUSTED_BADGE_HISTORY, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(TrustedBadgeHistory::ID, TrustedBadgeHistory::ID_LENGTH)
                ->primary();

            $table->char(TrustedBadgeHistory::MERCHANT_ID, TrustedBadgeHistory::ID_LENGTH);

            $table->string(TrustedBadgeHistory::STATUS, TrustedBadgeHistory::STATUS_LENGTH)
                ->nullable(false);

            $table->string(TrustedBadgeHistory::MERCHANT_STATUS, TrustedBadgeHistory::MERCHANT_STATUS_LENGTH)
                ->nullable(false)
                ->default('');

            $table->integer(TrustedBadgeHistory::CREATED_AT);

            $table->index(TrustedBadgeHistory::MERCHANT_ID);

            $table->index(TrustedBadgeHistory::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::TRUSTED_BADGE_HISTORY);
    }
}
