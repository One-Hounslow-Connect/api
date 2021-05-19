<?php

namespace App\Models;

use App\Models\Relationships\ServiceEligibilityRelationships;
use Illuminate\Database\Eloquent\Model;

class ServiceEligibility extends Model
{
    use ServiceEligibilityRelationships;

    protected $fillable = ['id', 'taxonomy_id'];

    /**
     * @return \App\Models\ServiceTaxonomy
     */
    public function touchService(): ServiceEligibility
    {
        $this->service->save();

        return $this;
    }
}
