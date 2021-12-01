<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralsEntity;
class CreateM2mReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::M2M_REFERRAL, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            //columns
            $table->char(M2MReferralsEntity::ID, M2MReferralsEntity::ID_LENGTH)
                  ->primary();

            $table->char(M2MReferralsEntity::MERCHANT_ID, M2MReferralsEntity::ID_LENGTH);
            $table->char(M2MReferralsEntity::REFERRER_ID, M2MReferralsEntity::ID_LENGTH)->nullable();;

            $table->json(M2MReferralsEntity::METADATA)
                  ->nullable();

            $table->char(M2MReferralsEntity::STATUS,30);
            $table->char(M2MReferralsEntity::REFERRER_STATUS,30)->nullable();

            $table->integer(M2MReferralsEntity::CREATED_AT);

            $table->integer(M2MReferralsEntity::UPDATED_AT);

            //index
            $table->index(M2MReferralsEntity::MERCHANT_ID);

            $table->index(M2MReferralsEntity::REFERRER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::M2M_REFERRAL);
    }
}
