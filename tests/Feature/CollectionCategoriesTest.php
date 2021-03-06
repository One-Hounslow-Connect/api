<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CollectionCategoriesTest extends TestCase
{
    /*
     * List all the category collections.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->json('GET', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'name',
            'intro',
            'icon',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
    }

    /*
     * List all the category collections.
     */

    public function test_guest_can_list_all_of_them()
    {
        $collectionCategoryCount = Collection::categories()->count();

        $response = $this->json('GET', '/core/v1/collections/categories/all');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'intro',
                    'icon',
                    'order',
                    'enabled',
                    'sideboxes' => [
                        '*' => [
                            'title',
                            'content',
                        ],
                    ],
                    'category_taxonomies' => [
                        '*' => [
                            'id',
                            'parent_id',
                            'name',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $response->assertJsonCount($collectionCategoryCount, 'data');
    }

    public function test_guest_can_list_enabled_and_disabled_collections()
    {
        $disabledCollection = Collection::where('type', 'category')->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::where('type', 'category')->latest()->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $disabledCollection->id,
            'enabled' => false,
        ]);

        $response->assertJsonFragment([
            'id' => $enabledCollection->id,
            'enabled' => true,
        ]);
    }

    public function test_guest_can_list_all_enabled_and_disabled_collections()
    {
        $disabledCollection = Collection::where('type', 'category')->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::where('type', 'category')->latest()->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/categories/all');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $disabledCollection->id,
            'enabled' => false,
        ]);

        $response->assertJsonFragment([
            'id' => $enabledCollection->id,
            'enabled' => true,
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/collections/categories');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Create a collection category.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonResource([
            'id',
            'name',
            'intro',
            'icon',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);
    }

    public function test_super_admin_can_create_a_disabled_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
    }

    public function test_order_is_updated_when_created_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 4,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 4]);
    }

    public function test_order_cannot_be_less_than_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 0,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 4,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific category collection.
     */

    public function test_guest_can_view_one()
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'name',
            'intro',
            'icon',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $collectionCategory->id,
            'name' => $collectionCategory->name,
            'intro' => $collectionCategory->meta['intro'],
            'icon' => $collectionCategory->meta['icon'],
            'order' => $collectionCategory->order,
            'enabled' => $collectionCategory->enabled,
            'sideboxes' => $collectionCategory->meta['sideboxes'],
            'created_at' => $collectionCategory->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionCategory->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Update a specific category collection.
     */

    public function test_guest_cannot_update_one()
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceWorker($service);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceAdmin($service);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $user->makeOrganisationAdmin($organisation);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'name',
            'intro',
            'icon',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_global_admin_cannot_update_status()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_super_admin_can_update_status()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'name',
            'intro',
            'icon',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_order_is_updated_when_updated_to_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$third->id}", [
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 1]);
    }

    public function test_order_is_updated_when_updated_to_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
    }

    public function test_order_is_updated_when_updated_to_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 3,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_cannot_be_less_than_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 0,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'icon' => 'info',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Delete a specific category collection.
     */

    public function test_guest_cannot_delete_one()
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceWorker($service);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $user->makeServiceAdmin($service);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $user->makeOrganisationAdmin($organisation);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeGlobalAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $category->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $category->id]);
    }

    public function test_order_is_updated_when_deleted_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$first->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $first->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$second->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $second->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'icon' => 'info',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$third->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $third->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }
}
