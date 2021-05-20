<?php

namespace App\Models;

use App\Models\Relationships\ServiceEligibilityRelationships;
use App\Models\Model;

class ServiceEligibility extends Model
{
    use ServiceEligibilityRelationships;

    /**
     * @return \App\Models\ServiceTaxonomy
     */
    public function touchService(): ServiceEligibility
    {
        $this->service->save();

        return $this;
    }
}
