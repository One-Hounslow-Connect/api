<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Laravel\Passport\Passport;
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
                    'taxonomies' => $service->serviceEligibilities['age_group']['taxonomies'],
                    'custom' => $service->eligibility_age_group_custom,
                ],
                'disability' => [
                    'taxonomies' => $service->serviceEligibilities['disability']['taxonomies'],
                    'custom' => $service->eligibility_disability_custom,
                ],
                'ethnicity' => [
                    'taxonomies' => $service->serviceEligibilities['ethnicity']['taxonomies'],
                    'custom' => $service->eligibility_ethnicity_custom,
                ],
                'gender' => [
                    'taxonomies' => $service->serviceEligibilities['gender']['taxonomies'],
                    'custom' => $service->eligibility_gender_custom,
                ],
                'income' => [
                    'taxonomies' => $service->serviceEligibilities['income']['taxonomies'],
                    'custom' => $service->eligibility_income_custom,
                ],
                'language' => [
                    'taxonomies' => $service->serviceEligibilities['language']['taxonomies'],
                    'custom' => $service->eligibility_language_custom,
                ],
            ]
        ]);
    }

    public function test_taxonomy_can_not_be_added_if_top_level_child_of_incorrect_parent_taxonomy()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        // When I try to associate a taxonomy that is NOT a child of Service Eligibility
        $incorrectTaxonomyId = Taxonomy::category()->children->random()->id;

        $payload = [
            'service_eligibility_types' => [
                'age_group' => $incorrectTaxonomyId,
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // A validation error is thrown
        $response->assertStatus(422);
    }

    private function createService()
    {
        $service = factory(Service::class)
            ->states(
                'withOfferings',
                'withUsefulInfo',
                'withSocialMedia',
                'withCustomEligibilities',
                'withEligibilityTaxonomies'
            )
            ->create();

        $service->save();
        return $service;
    }
}
