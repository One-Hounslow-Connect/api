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
        $this->generateServiceEligibilityTaxonomy();
    }

    public function test_criteria_property_returns_comma_separated_service_eligibilities()
    {
        $service = $this->createService();
        $response = $this->get('/core/v1/services/' . $service->id);

        $response->assertJsonFragment([
            'criteria' => [
                'age_group' => 'Age Group taxonomy child,custom age group',
                'disability' => 'Disability taxonomy child,custom disability',
                'employment' => 'Employment taxonomy child,custom employment',
                'gender' => 'Gender taxonomy child,custom gender',
                'housing' => 'Housing taxonomy child,custom housing',
                'income' => 'Income taxonomy child,custom income',
                'language' => 'Language taxonomy child,custom language',
            ]
        ]);
    }

    private function  createService()
    {
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);

        Taxonomy::serviceEligibility()->children->each(function($taxonomy) use ($service) {
            $service->serviceEligibilities()->create([
                'id' => (string) Str::uuid(),
                'taxonomy_id' => $taxonomy->id,
            ]);
        });
        $service->eligibility_age_group_custom = 'custom age group';
        $service->eligibility_disability_custom = 'custom disability';
        $service->eligibility_employment_custom = 'custom employment';
        $service->eligibility_gender_custom = 'custom gender';
        $service->eligibility_housing_custom = 'custom housing';
        $service->eligibility_income_custom = 'custom income';
        $service->eligibility_language_custom = 'custom language';


        $service->save();
        return $service;
    }

    private function generateServiceEligibilityTaxonomy(): void
    {
        $parents = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Age Group',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Disability',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Employment',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Gender',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Housing',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Income',
                'order' => 0,
                'depth' => 0,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Language',
                'order' => 0,
                'depth' => 0,
            ],
        ];

        Taxonomy::serviceEligibility()
            ->children()
            ->createMany($parents);

        Taxonomy::serviceEligibility()
            ->children()
            ->each(function ($item) {
                $item->children()->create([
                    'id' => (string) Str::uuid(),
                    'name' => $item . ' taxonomy child',
                    'order' => 0,
                    'depth' => 0,
                ]);
            });
    }
}
