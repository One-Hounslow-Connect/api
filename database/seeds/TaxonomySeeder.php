<?php

use App\Models\CollectionTaxonomy;
use App\Models\Referral;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
    }

    /**
     * Delete all current taxonomies
     *
     * @return null
     * @author Stuart Laverick
     **/
    public function deleteAllTaxonomies()
    {
        DB::table((new ServiceTaxonomy())->getTable())->truncate();
        DB::table((new CollectionTaxonomy())->getTable())->truncate();
        DB::table((new Taxonomy())->getTable())->truncate();
        DB::table((new Referral())->getTable())
            ->whereNotNull('organisation_taxonomy_id')
            ->update(['organisation_taxonomy_id' => null]);
    }
}
