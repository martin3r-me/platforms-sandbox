<?php

namespace Platform\Sandbox\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Sandbox\Enums\SandboxProjectStatus;
use Platform\Sandbox\Models\SandboxProject;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Services\EntityDimensionBridge;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $baseTeam = $user?->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        if (!$user || !$teamId) {
            return view('sandbox::livewire.sidebar', [
                'statusGroups' => collect(),
            ]);
        }

        // 1. Load all projects for this team
        $projects = SandboxProject::where('team_id', $teamId)
            ->with('phases')
            ->orderBy('name')
            ->get();

        // 2. Get entity links for projects
        $projectIds = $projects->pluck('id')->toArray();
        $entityItemMap = []; // entity_id => [project_ids]
        $linkedProjectIds = [];

        try {
            if (!empty($projectIds)) {
                $projectLinks = EntityDimensionBridge::linksForLinkables(
                    ['sandbox_project', SandboxProject::class],
                    $projectIds
                );
                foreach ($projectLinks as $link) {
                    $entityItemMap[$link->entity_id][] = $link->linkable_id;
                    $linkedProjectIds[] = $link->linkable_id;
                }
            }
        } catch (\Throwable $e) {
            // Organization module not loaded
        }

        $linkedProjectIds = array_unique($linkedProjectIds);

        // 3. Ancestor traversal for tree display
        $directEntityIds = array_keys($entityItemMap);
        if (!empty($directEntityIds)) {
            $directEntities = OrganizationEntity::with(['allParents.type'])
                ->whereIn('id', $directEntityIds)
                ->get()
                ->keyBy('id');

            foreach ($directEntities as $entityId => $entity) {
                $ancestor = $entity->allParents;
                while ($ancestor) {
                    if (!isset($entityItemMap[$ancestor->id])) {
                        $entityItemMap[$ancestor->id] = [];
                    }
                    $ancestor = $ancestor->allParents;
                }
            }
        }

        // 4. Load all relevant entities
        $entityIds = array_keys($entityItemMap);
        $entities = collect();
        $entityChildrenMap = [];
        $rootEntityIds = [];

        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            foreach ($entities as $entity) {
                $parentId = $entity->parent_entity_id;
                if ($parentId && $entities->has($parentId)) {
                    $entityChildrenMap[$parentId][] = $entity->id;
                } else {
                    $rootEntityIds[] = $entity->id;
                }
            }
        }

        // 5. Group projects by status, then build entity tree per status
        $statusOrder = [
            SandboxProjectStatus::ACTIVE,
            SandboxProjectStatus::PAUSED,
            SandboxProjectStatus::DRAFT,
            SandboxProjectStatus::COMPLETED,
            SandboxProjectStatus::CANCELLED,
        ];

        $projectsByStatus = $projects->groupBy(fn ($p) => $p->status->value);

        $statusGroups = collect();

        foreach ($statusOrder as $status) {
            $statusProjects = $projectsByStatus->get($status->value, collect());
            if ($statusProjects->isEmpty()) {
                continue;
            }

            $statusProjectIds = $statusProjects->pluck('id')->toArray();

            // Build entity tree for only this status's projects
            $statusEntityItemMap = [];
            foreach ($entityItemMap as $entityId => $pIds) {
                $filtered = array_intersect($pIds, $statusProjectIds);
                if (!empty($filtered)) {
                    $statusEntityItemMap[$entityId] = $filtered;
                }
            }

            // Mark ancestors needed for this status
            $statusEntityIds = array_keys($statusEntityItemMap);
            if (!empty($statusEntityIds) && $entities->isNotEmpty()) {
                foreach ($statusEntityIds as $entityId) {
                    $entity = $entities->get($entityId);
                    if (!$entity) continue;
                    $ancestor = $entity->parent_entity_id ? $entities->get($entity->parent_entity_id) : null;
                    while ($ancestor) {
                        if (!isset($statusEntityItemMap[$ancestor->id])) {
                            $statusEntityItemMap[$ancestor->id] = [];
                        }
                        $ancestor = $ancestor->parent_entity_id ? $entities->get($ancestor->parent_entity_id) : null;
                    }
                }
            }

            // Build tree for this status
            $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $statusEntityItemMap, $statusProjects): ?array {
                $entity = $entities->get($entityId);
                if (!$entity) {
                    return null;
                }

                if (!isset($statusEntityItemMap[$entityId])) {
                    return null;
                }

                $childIds = $entityChildrenMap[$entityId] ?? [];
                $childNodes = collect($childIds)
                    ->map(fn ($childId) => $buildTree($childId))
                    ->filter();

                $childrenByType = $childNodes
                    ->groupBy(fn ($child) => $child['type_id'])
                    ->map(function ($group) use ($entities) {
                        $firstChild = $group->first();
                        $typeEntity = $entities->get($firstChild['entity_id']);
                        $type = $typeEntity?->type;

                        return [
                            'type_id' => $firstChild['type_id'],
                            'type_name' => $type?->name ?? 'Sonstige',
                            'type_icon' => $type?->icon ?? null,
                            'sort_order' => $type?->sort_order ?? 999,
                            'children' => $group->sortBy('entity_name')->values(),
                        ];
                    })
                    ->sortBy('sort_order')
                    ->values();

                $itemData = $statusEntityItemMap[$entityId] ?? [];
                $entityProjects = collect($itemData)
                    ->map(fn ($id) => $statusProjects->firstWhere('id', $id))
                    ->filter()
                    ->values();

                $totalItems = $entityProjects->count();
                foreach ($childNodes as $child) {
                    $totalItems += $child['total_items'];
                }

                if ($totalItems === 0) {
                    return null;
                }

                return [
                    'entity_id' => $entityId,
                    'entity_name' => $entity->name,
                    'type_id' => $entity->type?->id,
                    'projects' => $entityProjects,
                    'children_by_type' => $childrenByType,
                    'total_items' => $totalItems,
                ];
            };

            // Root entities grouped by type for this status
            $groupedByType = [];
            foreach ($rootEntityIds as $entityId) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }

                $tree = $buildTree($entityId);
                if (!$tree) {
                    continue;
                }

                $typeId = $entity->type->id;
                if (!isset($groupedByType[$typeId])) {
                    $groupedByType[$typeId] = [
                        'type_id' => $typeId,
                        'type_name' => $entity->type->name,
                        'type_icon' => $entity->type->icon,
                        'sort_order' => $entity->type->sort_order ?? 999,
                        'entities' => [],
                    ];
                }
                $groupedByType[$typeId]['entities'][] = $tree;
            }

            $entityTypeGroups = collect($groupedByType)
                ->sortBy('sort_order')
                ->map(function ($group) {
                    $group['entities'] = collect($group['entities'])
                        ->sortBy('entity_name')
                        ->values();
                    return $group;
                })
                ->values();

            // Unlinked projects for this status
            $unlinkedForStatus = $statusProjects
                ->filter(fn ($p) => !in_array($p->id, $linkedProjectIds))
                ->values();

            $statusGroups->push([
                'status' => $status,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $statusProjects->count(),
                'linked' => $entityTypeGroups,
                'unlinked' => $unlinkedForStatus,
            ]);
        }

        return view('sandbox::livewire.sidebar', [
            'statusGroups' => $statusGroups,
        ]);
    }
}
