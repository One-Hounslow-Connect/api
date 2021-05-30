<?php

namespace App\Models;

use App\Models\Relationships\ServiceEligibilityRelationships;

class ServiceEligibility extends Model
{
    use ServiceEligibilityRelationships;

    const SUPPORTED_CUSTOM_FIELD_NAMES = [
        'age_group',
        'disability',
        'employment',
        'gender',
        'housing',
        'income',
        'language',
        'ethnicity',
        'other',
    ];

    /**
     * @return \App\Models\ServiceTaxonomy
     */
    public function touchService(): ServiceEligibility
    {
        $this->service->save();

        return $this;
    }
}
