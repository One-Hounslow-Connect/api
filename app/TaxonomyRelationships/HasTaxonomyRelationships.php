<?php

namespace App\TaxonomyRelationships;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasTaxonomyRelationships
{
    /**
     * Return the Taxonomy relationship for the class
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function taxonomyRelationship(): HasMany;
}
