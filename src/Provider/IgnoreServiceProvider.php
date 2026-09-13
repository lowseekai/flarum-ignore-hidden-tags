<?php

namespace Nodeloc\IgnoreHiddenTags\Provider;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Tags\Search\HideHiddenTagsFromAllDiscussionsPage;
use Illuminate\Support\Arr;
use Nodeloc\IgnoreHiddenTags\Filter\IgnoreHiddenTagsFromAllDiscussionsPage as IgnoreHiddenTagsMutator;

class IgnoreServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->extend('flarum.search.mutators', function (array $mutators): array {
            $discussionMutators = Arr::get($mutators, DiscussionSearcher::class, []);

            $discussionMutators = array_values(array_filter(
                $discussionMutators,
                fn ($mutator) => $mutator !== HideHiddenTagsFromAllDiscussionsPage::class
                    && ! $mutator instanceof HideHiddenTagsFromAllDiscussionsPage
            ));

            $discussionMutators[] = IgnoreHiddenTagsMutator::class;
            $mutators[DiscussionSearcher::class] = $discussionMutators;

            return $mutators;
        });
    }
}
