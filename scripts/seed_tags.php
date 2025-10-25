<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Bootstrap\App;
use App\Repositories\TagRepository;

$app = new App();
$container = $app->container();

/** @var TagRepository $tags */
$tags = $container->get(TagRepository::class);

$seedTags = [
    ['name' => 'AI', 'slug' => 'ai'],
    ['name' => 'Startups', 'slug' => 'startups'],
    ['name' => 'Technology', 'slug' => 'technology'],
    ['name' => 'Cybersecurity', 'slug' => 'cybersecurity'],
    ['name' => 'Climate', 'slug' => 'climate'],
    ['name' => 'Science', 'slug' => 'science'],
    ['name' => 'Health', 'slug' => 'health'],
    ['name' => 'Business', 'slug' => 'business'],
    ['name' => 'Programming', 'slug' => 'programming'],
    ['name' => 'Design', 'slug' => 'design'],
];

foreach ($seedTags as $tag) {
    $existing = $tags->findBySlug($tag['slug']);
    
    if ($existing) {
        echo sprintf("Tag already exists: %s (%s)\n", $tag['name'], $tag['slug']);
        continue;
    }
    
    $record = $tags->create($tag['name'], $tag['slug']);
    
    echo sprintf("Seeded tag: %s (%s) - ID: %d\n", $tag['name'], $tag['slug'], $record['id']);
}
