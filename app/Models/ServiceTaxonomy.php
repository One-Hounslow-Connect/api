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
    public function touchService(): ServiceTaxonomy
    {
        $this->service->save();

        return $this;
    }
}
