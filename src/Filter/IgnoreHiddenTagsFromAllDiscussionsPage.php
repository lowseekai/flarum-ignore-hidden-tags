<?php

namespace Nodeloc\IgnoreHiddenTags\Filter;

use Flarum\Group\Group;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Tags\Tag;

class IgnoreHiddenTagsFromAllDiscussionsPage
{
    private const PERMISSION = 'ignorehiddentags.allowIgnoreGroup';

    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if (count($state->getActiveFilters()) > 0 || $state->isFulltextSearch()) {
            return;
        }

        $hiddenTagIds = Tag::where('is_hidden', 1)->pluck('id');

        if ($hiddenTagIds->isEmpty()) {
            return;
        }

        $query = $state->getQuery();

        $applyFilter = function ($builder) use ($hiddenTagIds): void {
            $builder->whereNotIn('discussions.id', function ($subquery) use ($hiddenTagIds) {
                $subquery
                    ->select('discussion_id')
                    ->from('discussion_tag')
                    ->whereIn('tag_id', $hiddenTagIds)
                    ->whereNotExists(function ($permissionQuery) {
                        $permissionQuery
                            ->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'discussions.user_id')
                            ->where(function ($groupQuery) {
                                $groupQuery
                                    ->whereExists(function ($assignedGroupQuery) {
                                        $assignedGroupQuery
                                            ->selectRaw('1')
                                            ->from('group_user')
                                            ->whereColumn('group_user.user_id', 'users.id')
                                            ->where('group_user.group_id', Group::ADMINISTRATOR_ID);
                                    })
                                    ->orWhere(function ($memberGroupQuery) {
                                        $memberGroupQuery->where('users.is_email_confirmed', 1)
                                            ->where(function ($permissionGroupQuery) {
                                                $permissionGroupQuery
                                                    ->whereExists(function ($memberPermissionQuery) {
                                                        $memberPermissionQuery
                                                            ->selectRaw('1')
                                                            ->from('group_permission')
                                                            ->where('group_permission.group_id', Group::MEMBER_ID)
                                                            ->where('group_permission.permission', self::PERMISSION);
                                                    })
                                                    ->orWhereExists(function ($assignedPermissionQuery) {
                                                        $assignedPermissionQuery
                                                            ->selectRaw('1')
                                                            ->from('group_user')
                                                            ->join(
                                                                'group_permission',
                                                                'group_permission.group_id',
                                                                '=',
                                                                'group_user.group_id'
                                                            )
                                                            ->whereColumn('group_user.user_id', 'users.id')
                                                            ->where('group_permission.permission', self::PERMISSION);
                                                    });
                                            });
                                    });
                            });
                    });
            });
        };

        $applyFilter($query);

        // Keep the constraint when another mutator has already created a union.
        foreach ($query->getQuery()->unions ?? [] as $union) {
            $applyFilter($union['query']);
        }
    }
}
