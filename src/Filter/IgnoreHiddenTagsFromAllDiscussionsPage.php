<?php

namespace Nodeloc\IgnoreHiddenTags\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Tags\Tag;

class IgnoreHiddenTagsFromAllDiscussionsPage
{
    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if (count($state->getActiveFilters()) > 0 || $state->isFulltextSearch()) {
            return;
        }

        if ($state->getActor()->hasPermission('ignorehiddentags.allowIgnoreGroup')) {
            return;
        }

        $hiddenTagIds = Tag::where('is_hidden', 1)->pluck('id');
        $query = $state->getQuery();

        $applyFilter = function ($builder) use ($hiddenTagIds): void {
            $builder->whereNotIn('discussions.id', function ($subquery) use ($hiddenTagIds) {
                $subquery
                    ->select('discussion_id')
                    ->from('discussion_tag')
                    ->whereIn('tag_id', $hiddenTagIds);
            });
        };

        $applyFilter($query);

        // Keep the constraint when another mutator has already created a union.
        foreach ($query->getQuery()->unions ?? [] as $union) {
            $applyFilter($union['query']);
        }
    }
}
