<?php

namespace App\TaxonomyRelationships;

use App\Models\Model;
use App\Models\Taxonomy;
use Illuminate\Support\Collection;

trait UpdateServiceEligibilityTaxonomyRelationships
{
    /**
     * @param \Illuminate\Support\Collection $taxonomies
     * @return \App\Models\Model
     */
    public function syncEligibilityRelationships(Collection $taxonomies): Model
    {
        // Delete all existing taxonomy relationships
        $this->serviceEligibilities()->delete();

        // Create a taxonomy relationship record for each taxonomy and their parents.
        foreach ($taxonomies as $taxonomy) {
            $this->createEligibilityRelationship($taxonomy);
        }

        return $this;
    }

    /**
     * @param \App\Models\Taxonomy $taxonomy
     * @return \App\Models\Model
     */
    protected function createEligibilityRelationship(Taxonomy $taxonomy): Model
    {
        return $this->serviceEligibilities()->updateOrCreate(['taxonomy_id' => $taxonomy->id]);
    }
}
