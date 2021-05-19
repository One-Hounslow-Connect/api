<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomEligibilityTypeFieldsToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('eligibility_age_group_custom')->nullable();
            $table->text('eligibility_disability_custom')->nullable();
            $table->text('eligibility_employment_custom')->nullable();
            $table->text('eligibility_gender_custom')->nullable();
            $table->text('eligibility_housing_custom')->nullable();
            $table->text('eligibility_income_custom')->nullable();
            $table->text('eligibility_language_custom')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'eligibility_age_group_custom',
                'eligibility_disability_custom',
                'eligibility_employment_custom',
                'eligibility_gender_custom',
                'eligibility_housing_custom',
                'eligibility_income_custom',
                'eligibility_language_custom',
            ]);
        });
    }
}
