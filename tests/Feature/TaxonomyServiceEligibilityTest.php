<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxonomyServiceEligibilityTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_service_has_correct_eligibility_schema()
    {
        $service = $this->createService();

        $response = $this->get(route('core.v1.services.show', $service->id));

        $response->assertJsonFragment([
            'eligibility_types' => [
                'age_group' => [
                    'taxonomies' => $service->serviceEligibilities['age_group'],
                    'custom' => $service->service_eligibility_age_group_custom,
                ],
                'disability' => [
                    'taxonomies' => $service->serviceEligibilities['disability'],
                    'custom' => $service->service_eligibility_disability_custom,
                ],
                'gender' => [
                    'taxonomies' => $service->serviceEligibilities['gender'],
                    'custom' => $service->service_eligibility_gender_custom,
                ],
                'income' => [
                    'taxonomies' => $service->serviceEligibilities['income'],
                    'custom' => $service->service_eligibility_income_custom,
                ],
                'language' => [
                    'taxonomies' => $service->serviceEligibilities['language'],
                    'custom' => $service->service_eligibility_language_custom,
                ],
                'ethnicity' => [
                    'taxonomies' => $service->serviceEligibilities['ethnicity'],
                    'custom' => $service->service_eligibility_ethnicity_custom,
                ],
            ]
        ]);
    }

    private function createService()
    {
        $service = factory(Service::class)
            ->states('withOfferings', 'withUsefulInfo', 'withSocialMedia')
            ->create();

        // Loop through each top level child of service eligibility taxonomy
        Taxonomy::serviceEligibility()->children->each((function($topLevelChild) use ($service) {
            // And for each top level child, attach one of its children to the service
            $service->serviceEligibilities()->create([
                'taxonomy_id' => $topLevelChild->children->first()->id,
            ]);
        }));

        $service->eligibility_age_group_custom = 'custom age group';
        $service->eligibility_disability_custom = 'custom disability';
        $service->eligibility_employment_custom = 'custom employment';
        $service->eligibility_gender_custom = 'custom gender';
        $service->eligibility_housing_custom = 'custom housing';
        $service->eligibility_income_custom = 'custom income';
        $service->eligibility_language_custom = 'custom language';
        $service->eligibility_ethnicity_custom = 'custom ethnicity';
        $service->eligibility_other_custom = 'custom other';

        $service->save();
        return $service;
    }
}
