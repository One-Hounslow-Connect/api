<?php

namespace App\Models\Mutators;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ServiceMutators
{
    public function getServiceEligibilitiesAttribute(): Collection
    {
        $keyedByName = [];

        $this->serviceEligibilities()
            ->get()
            ->each(function ($item) use (&$keyedByName) {
                $key = Str::snake($item->taxonomy->parent->name);

                $keyedByName[$key] = [
                    'taxonomies' => [],
                    'custom' => [],
                ];

                // CH-303: Fred here - I'd quite like to have had an array of the actual Taxonomy models here, but this
                // is out of scope for the current work and it simplifies things a bit to bring the data together here.
                // if more eligibility data is required in the API response in future, this method and the resource
                // class can always be refactored.
                $keyedByName[$key]['taxonomies'][] = $item->taxonomy_id;
                $keyedByName[$key]['custom'] = $this->getCustomEligibilityField($key);
            });

        // I feel like this is kind of inefficient as we have to return the whole array every time and then search it
        return new Collection($keyedByName);
    }

    private function getCustomEligibilityField(string $fieldName)
    {
        $customFieldName = 'eligibility_' . $fieldName . '_custom';

        return $this->{$customFieldName};
    }
}
