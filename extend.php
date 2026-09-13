<?php

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Nodeloc\IgnoreHiddenTags\Provider\IgnoreServiceProvider;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),
    new Extend\Locales(__DIR__.'/locale'),
    (new Extend\ServiceProvider())
        ->register(IgnoreServiceProvider::class),
    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('allowIgnoreGroup')
                ->get(fn ($model, Context $context) => $context->getActor()->hasPermission('ignorehiddentags.allowIgnoreGroup')),
        ]),
];
