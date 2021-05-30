<?php

namespace App\Models\Mutators;

use App\Models\Service;
use App\Models\ServiceEligibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ServiceMutators
{
    public function getServiceEligibilitiesAttribute(): Collection
    {
        $keyedByName = [];

        foreach (ServiceEligibility::SUPPORTED_CUSTOM_FIELD_NAMES as $fieldName) {
            $keyedByName[$fieldName]['custom'] = $this->getCustomEligibilityField($fieldName);

            // There is no 'other' taxonomy top-level child of Service Eligibility
            if ($fieldName !== 'other') {
                $keyedByName[$fieldName]['taxonomies'] = [];
            }
        }

        $this->serviceEligibilities()
            ->get()
            ->each(function ($item) use (&$keyedByName) {
                $key = Str::snake($item->taxonomy->parent->name);

                // CH-303: Fred here - I'd quite like to have had an array of the actual Taxonomy models here, but this
                // is out of scope for the current work and it simplifies things a bit to bring the data together here.
                // if more eligibility data is required in the API response in future, this method and the resource
                // class can always be refactored.
                $keyedByName[$key]['taxonomies'][] = $item->taxonomy_id;
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
