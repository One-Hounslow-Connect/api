<?php

namespace App\Models;

use App\Models\Mutators\ServiceTaxonomyMutators;
use App\Models\Relationships\ServiceTaxonomyRelationships;
use App\Models\Scopes\ServiceTaxonomyScopes;

class ServiceTaxonomy extends Model
{
    use ServiceTaxonomyMutators;
    use ServiceTaxonomyRelationships;
    use ServiceTaxonomyScopes;

    const SUPPORTED_CUSTOM_FIELD_NAMES = [
        'eligibility_age_group_custom',
        'eligibility_disability_custom',
        'eligibility_employment_custom',
        'eligibility_gender_custom',
        'eligibility_housing_custom',
        'eligibility_income_custom',
        'eligibility_language_custom',
        'eligibility_ethnicity_custom',
        'eligibility_other_custom',
    ];

    /**
     * @return \App\Models\ServiceTaxonomy
     */
    public function touchService(): ServiceTaxonomy
    {
        $this->service->save();

        return $this;
    }
}
