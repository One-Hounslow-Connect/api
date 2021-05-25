<?php

namespace App\Models\Mutators;

use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Str;

trait ServiceMutators
{
    public function getServiceEligibilitiesAttribute(): LaravelCollection
    {
        $keyedByName = new LaravelCollection();

        $this->serviceEligibilities()
            ->get()
            ->each(function ($item) use (&$keyedByName) {
                $key = Str::snake($item->taxonomy->parent->name);
                if (!array_key_exists($key, $keyedByName)) {
                    $keyedByName[$key] = [$item->taxonomy];
                }
                else {
                    $keyedByName[$key][] = $item->taxonomy;
                }
            });

        return $keyedByName;
    }
}
