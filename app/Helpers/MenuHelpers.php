<?php

namespace App\Helpers;

use App\Models\Menu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    public static function accessibleMenus(): Collection
    {
        // Eager load roles & permissions sekaligus — cegah N+1 dari Spatie
        $user = Auth::user()->load(['roles', 'permissions']);

        $allowedMenuIds = $user->getAllPermissions()
            ->pluck('menu_id')
            ->unique()
            ->filter()
            ->values();

        if ($allowedMenuIds->isEmpty()) {
            return collect();
        }

        $allowedMenus = Menu::whereIn('id', $allowedMenuIds)
            ->orderBy('order')
            ->get()
            ->keyBy('id');

        $children   = $allowedMenus->filter(fn($m) => $m->parent_id != '0');
        $directMenus = $allowedMenus->filter(fn($m) => $m->parent_id == '0');

        $parentIds = $children->pluck('parent_id')->unique()->filter();

        $parents = $parentIds->isNotEmpty()
            ? Menu::whereIn('id', $parentIds)
            ->orderBy('order')
            ->get()
            : collect();

        $allParents = $parents->merge($directMenus)
            ->sortBy('order')
            ->unique('id');

        return $allParents->map(function ($parent) use ($children) {
            $parent->children = $children
                ->where('parent_id', (string) $parent->id)
                ->values();

            return $parent;
        })->values();
    }
}
