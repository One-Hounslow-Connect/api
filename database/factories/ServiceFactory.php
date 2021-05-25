<?php

use App\Models\Service;
use App\Models\SocialMedia;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

$factory->define(Service::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'organisation_id' => function () {
            return factory(\App\Models\Organisation::class)->create()->id;
        },
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'type' => Service::TYPE_SERVICE,
        'status' => Service::STATUS_ACTIVE,
        'intro' => $faker->sentence,
        'description' => $faker->sentence,
        'is_free' => true,
        'url' => $faker->url,
        'contact_name' => $faker->name,
        'contact_phone' => random_uk_phone(),
        'contact_email' => $faker->safeEmail,
        'show_referral_disclaimer' => false,
        'referral_method' => Service::REFERRAL_METHOD_NONE,
        'last_modified_at' => Date::now(),
    ];
});

$factory->afterCreating(Service::class, function (Service $service, Faker $faker) {
    \App\Models\ServiceCriterion::create([
        'service_id' => $service->id,
        'age_group' => null,
        'disability' => null,
        'employment' => null,
        'gender' => null,
        'housing' => null,
        'income' => null,
        'language' => null,
        'other' => null,
    ]);
});

$factory->afterCreatingState(Service::class, 'withOfferings', function (Service $service, Faker $faker) {
    $service->offerings()->create([
        'offering' => 'Weekly club',
        'order' => 1,
    ]);
});

$factory->afterCreatingState(Service::class, 'withUsefulInfo', function (Service $service, Faker $faker) {
    $service->usefulInfos()->create([
        'title' => 'Did You Know?',
        'description' => 'This is a test description',
        'order' => 1,
    ]);
});

$factory->afterCreatingState(Service::class, 'withSocialMedia', function (Service $service, Faker $faker) {
    $service->socialMedias()->create([
        'type' => SocialMedia::TYPE_INSTAGRAM,
        'url' => 'https://www.instagram.com/ayupdigital/',
    ]);
});

