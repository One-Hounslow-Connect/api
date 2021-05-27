<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TaxonomyServiceEligibilityTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_service_has_correct_eligibility_response_schema()
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

    public function test_custom_fields_are_created_as_update_request_and_persisted_successfully_on_approval()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        $serviceAdmin->save();

        $ageGroupCustom = 'I am updating the custom Age Group';
        $disabilityCustom = 'I am updating the custom Disability';
        $employmentCustom = 'I am updating the custom Employment';
        $genderCustom = 'I am updating the custom Gender';
        $housingCustom = 'I am updating the custom Housing';
        $incomeCustom = 'I am updating the custom Income';
        $languageCustom = 'I am updating the custom Language';
        $ethnicityCustom = 'I am updating the custom Ethnicity';
        $otherCustom = 'I am updating the custom Other';

        $payload = [
            'eligibility_types' => [
                'custom' => [
                    'age_group' => $ageGroupCustom,
                    'disability' => $disabilityCustom,
                    'employment' => $employmentCustom,
                    'gender' => $genderCustom,
                    'housing' => $housingCustom,
                    'income' => $incomeCustom,
                    'language' => $languageCustom,
                    'ethnicity' => $ethnicityCustom,
                    'other' => $otherCustom,
                ]
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // I am successful
        $response->assertSuccessful();

        $updateRequest = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_SERVICE)
            ->where('updateable_id', $service->id)
            ->firstOrFail();

        $updateRequestData = $updateRequest->data;

        // And the service now has the appropriate custom eligibility fields set in an update request
        $this->assertEquals($ageGroupCustom, $updateRequestData['eligibility_types']['custom']['age_group']);
        $this->assertEquals($disabilityCustom, $updateRequestData['eligibility_types']['custom']['disability']);
        $this->assertEquals($employmentCustom, $updateRequestData['eligibility_types']['custom']['employment']);
        $this->assertEquals($genderCustom, $updateRequestData['eligibility_types']['custom']['gender']);
        $this->assertEquals($housingCustom, $updateRequestData['eligibility_types']['custom']['housing']);
        $this->assertEquals($incomeCustom, $updateRequestData['eligibility_types']['custom']['income']);
        $this->assertEquals($languageCustom, $updateRequestData['eligibility_types']['custom']['language']);
        $this->assertEquals($ethnicityCustom, $updateRequestData['eligibility_types']['custom']['ethnicity']);
        $this->assertEquals($otherCustom, $updateRequestData['eligibility_types']['custom']['other']);

        $globalAdmin = factory(User::class)->create();
        $globalAdmin->makeGlobalAdmin()->save();

        // And then when a global admin approves the changes
        Passport::actingAs($globalAdmin);

        $response = $this->json('PUT', route('core.v1.update-requests.approve', $updateRequest->id));
        $response->assertSuccessful();

        $service = $service->find($service->id);

        $this->assertEquals($ageGroupCustom, $service->eligibility_age_group_custom);
        $this->assertEquals($disabilityCustom, $service->eligibility_disability_custom);
        $this->assertEquals($employmentCustom, $service->eligibility_employment_custom);
        $this->assertEquals($genderCustom, $service->eligibility_gender_custom);
        $this->assertEquals($housingCustom, $service->eligibility_housing_custom);
        $this->assertEquals($incomeCustom, $service->eligibility_income_custom);
        $this->assertEquals($languageCustom, $service->eligibility_language_custom);
        $this->assertEquals($ethnicityCustom, $service->eligibility_ethnicity_custom);
        $this->assertEquals($otherCustom, $service->eligibility_other_custom);
    }

    public function test_taxonomy_id_are_created_as_update_request_and_persisted_successfully_on_approval()
    {
        $this->assertTrue(false, 'TODO: implement this test');
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
            'eligibility_types' => [
                'taxonomies' => [$incorrectTaxonomyId],
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // A validation error is thrown
        $response->assertStatus(422);
    }

    public function test_taxonomy_can_be_added_if_child_of_correct_service_eligibility_type()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        $serviceAdmin->save();

        // When I try to associate a taxonomy that IS a child of Service Eligibility, but NOT the correct type,
        // i.e. a gender eligibility attached to age_group
        $correctTaxonomyId = $this->randomEligibilityDescendant()->id;

        $payload = [
            'eligibility_types' => [
                'taxonomies' => [$correctTaxonomyId],
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // The response is successful
        $response->assertSuccessful();
    }

    public function test_empty_service_eligibility_types_array_is_accepted_by_validation()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        $serviceAdmin->save();

        // When I send an empty array for eligibility types
        $payload = [
            'eligibility_types' => [
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // Validation passes
        $response->assertSuccessful();
    }

    public function test_empty_service_eligibility_types_custom_array_is_accepted_by_validation()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        $serviceAdmin->save();

        // When I send an empty array for custom eligibility types
        $payload = [
            'eligibility_types' => [
                'custom' => [],
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // Validation passes
        $response->assertSuccessful();
    }

    public function test_null_service_eligibility_types_custom_array_element_is_accepted_by_validation()
    {
        // Given that I am updating an existing service
        $service = $this->createService();
        $serviceAdmin = factory(User::class)
            ->create()
            ->makeServiceAdmin($service);

        $serviceAdmin->save();

        // When I send a custom eligibility field set to null
        $payload = [
            'eligibility_types' => [
                'custom' => [
                    'age_group' => null,
                ],
            ],
        ];

        Passport::actingAs($serviceAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // Validation passes
        $response->assertSuccessful();
    }

    public function test_global_admin_can_update_eligibility_taxonomies()
    {
        $service = $this->createService();

        // Given that I am updating an existing service as a global admin
        $service = $this->createService();
        $globalAdmin = factory(User::class)
            ->create()
            ->makeGlobalAdmin();

        // When I try to associate a valid child taxonomy of Service Eligibility
        $taxonomyId = $this->randomEligibilityDescendant()->id;

        $payload = [
            'eligibility_types' => [
                'taxonomies' => [$taxonomyId],
            ],
        ];

        Passport::actingAs($globalAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // I am successful in doing so.
        $response->assertSuccessful();
    }

    public function test_organisation_admin_can_not_update_eligibility_taxonomies()
    {
        $service = $this->createService();

        // Given that I am updating an existing service as an organisation admin
        $service = $this->createService();
        $organisation = factory(Organisation::class)->create();

        $organisationAdmin = factory(User::class)
            ->create()
            ->makeOrganisationAdmin($organisation);

        // When I try to associate a valid child taxonomy of Service Eligibility
        $taxonomyId = $this->randomEligibilityDescendant()->id;

        $payload = [
            'eligibility_types' => [
                'taxonomies' => [$taxonomyId],
            ],
        ];

        Passport::actingAs($organisationAdmin);

        $response = $this->json('PUT', route('core.v1.services.update', $service->id), $payload);

        // I am unauthorized to do so
        $response->assertStatus(403);
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

        $service->syncTaxonomyRelationships(collect([Taxonomy::category()->children()->firstOrFail()]));
        $service->save();

        return $service;
    }

    private function randomTopLevelChild()
    {
        return Taxonomy::serviceEligibility()
            ->children()
            ->inRandomOrder()
            ->firstOrFail();
    }

    private function randomEligibilityDescendant()
    {
        return $this->randomTopLevelChild()
            ->children
            ->random();
    }
}
