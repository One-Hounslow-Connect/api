<?php
namespace App\Services\OpenActiveTaxonomy;
use App\Models\Taxonomy;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class Importer
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function runImport()
    {
        $response = $this->client->get('https://openactive.io/activity-list/activity-list.jsonld');
        $data = json_decode((string) $response->getBody(), true);
        $allTaxonomyData = collect($data['concept']);

        $topLevelTaxonomyData = $allTaxonomyData->filter(function ($taxonomy) {
            return (
                !array_key_exists('broader', $taxonomy)
                && array_key_exists('topConceptOf', $taxonomy)
            );
        });

        $childTaxonomyData = $allTaxonomyData->filter(function($taxonomy) {
            return (
                array_key_exists('broader', $taxonomy)
                && !array_key_exists('topConceptOf', $taxonomy)
            );
        });

//        dump(count($allTaxonomyData));
//        dump(count($topLevelTaxonomyData));
//        dump(count($childTaxonomyData));

        // @FIXME: we're looping through the same set of data  and not filtering out children from parents, so
        // this will just cause a bunch of unique ID collisions once the child taxonomies collection is processed.
        $mappedTopLevelTaxonomies = $topLevelTaxonomyData->map(function($taxonomy) {
            return $this->mapOpenActiveTaxonomyToTaxonomyModelSchema($taxonomy);
        });

        $mappedChildTaxonomies = $topLevelTaxonomyData->map(function($taxonomy) {
            return $this->mapOpenActiveTaxonomyToTaxonomyModelSchema($taxonomy);
        });

//        dump($mappedTopLevelTaxonomies);

        $openActiveTopLevelTaxonomy = Taxonomy::where(['name' => 'OpenActive Taxonomy'])->first();

        $openActiveTopLevelTaxonomy->children()->createMany($mappedTopLevelTaxonomies->toArray());

        dump($openActiveTopLevelTaxonomy->children);

        DB::transaction(function() use ($mappedChildTaxonomies) {
            foreach ($mappedChildTaxonomies as $childTaxonomy) {
                Taxonomy::updateOrCreate($childTaxonomy);
            }
        });
    }

    private function parseIdentifier(string $identifierUrl)
    {
        return substr($identifierUrl, strpos($identifierUrl, "#") + 1);
    }

    private function mapOpenActiveTaxonomyToTaxonomyModelSchema(array $taxonomyData) {
        return [
            'id' => $taxonomyData['identifier'],
            'name' => $taxonomyData['prefLabel'],
            'parent_id' => array_key_exists('broader', $taxonomyData) ? $this->parseIdentifier($taxonomyData['broader'][0]) : 'd3598dc2-db24-4494-a511-d5bad920d583',
            'order' => 0,
            'depth' => 2,
        ];
    }
}
