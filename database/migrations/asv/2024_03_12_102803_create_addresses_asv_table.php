<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->char('id', 14)->collation('utf8_bin');
            $table->char('entity_id', 14)->nullable()->collation('utf8_bin');
            $table->string('entity_type', 32)->nullable()->collation('utf8_bin');
            $table->string('line1', 255)->nullable(false)->collation('utf8_bin');
            $table->string('line2', 255)->nullable()->collation('utf8_bin');
            $table->string('city', 64)->nullable()->collation('utf8_bin');
            $table->string('zipcode', 16)->nullable()->collation('utf8_bin');
            $table->string('state', 64)->nullable()->collation('utf8_bin');
            $table->string('country', 64)->nullable(false)->collation('utf8_bin');
            $table->string('type', 32)->nullable(false)->collation('utf8_bin');
            $table->tinyInteger('primary')->nullable(false);
            $table->integer('deleted_at')->nullable()->default(null);
            $table->integer('created_at')->nullable(false);
            $table->integer('updated_at')->nullable(false);
            $table->string('contact', 20)->nullable()->collation('utf8_bin');
            $table->string('tag', 32)->nullable()->collation('utf8_bin');
            $table->string('landmark', 255)->nullable()->collation('utf8_bin');
            $table->string('name', 64)->nullable()->collation('utf8_bin');
            $table->char('source_id', 14)->nullable()->collation('utf8mb4_bin');
            $table->string('source_type', 20)->nullable()->collation('utf8_bin');

            $table->primary('id');
            $table->index('country', 'addresses_country_index');
            $table->index('state', 'addresses_state_index');
            $table->index('type', 'addresses_type_index');
            $table->index('entity_id', 'addresses_entity_id_index');
            $table->index('entity_type', 'addresses_entity_type_index');
            $table->index('primary', 'addresses_primary_index');
            $table->index('deleted_at', 'addresses_deleted_at_index');
            $table->index('created_at', 'addresses_created_at_index');
            $table->index('updated_at', 'addresses_updated_at_index');
            $table->index('contact', 'addresses_contact_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};
