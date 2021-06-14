<?php

use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpenActiveTaxonomyTopLeveLParent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $categoryId = Taxonomy::category()->id;
        $openActiveTaxonomyId = uuid();
        $nowDateTimeString = Carbon::now()->toDateTimeString();

        // Create LGA Standards Taxonomy as child of Category
        DB::table((new Taxonomy())->getTable())->insert(
            [
                'id' => $openActiveTaxonomyId,
                'parent_id' => $categoryId,
                'name' => 'OpenActive Taxonomy',
                'order' => 0,
                'depth' => 1,
                'created_at' => $nowDateTimeString,
                'updated_at' => $nowDateTimeString,
            ]
        );

        Taxonomy::category()->updateDepth();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $categoryId = Taxonomy::category()->id;
        $openActiveId = DB::table((new Taxonomy())->getTable())
            ->where('parent_id', $categoryId)
            ->where('name', 'OpenActive Taxonomy')
            ->value('id');

        DB::table((new ServiceTaxonomy())->getTable())
            ->where('taxonomy_id', $openActiveId)
            ->delete();

        Taxonomy::category()->updateDepth();

        DB::table((new Taxonomy())->getTable())
            ->where('id', $openActiveId)
            ->delete();
    }
}
