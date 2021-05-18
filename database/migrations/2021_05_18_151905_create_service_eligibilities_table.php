<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceEligibilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_eligibilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id', 'services');
            $table->foreignUuid('taxonomy_id', 'taxonomies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_eligibilities');
    }
}
