<?php

namespace App\Support;

use App\Models\Category;
use App\Models\User;

class DefaultCategories
{
    public static function ensureFor(User $user): void
    {
        if ($user->categories()->exists()) {
            return;
        }

        collect([
            ['name' => 'Alimentacion', 'color' => '#16a34a', 'icon' => 'utensils'],
            ['name' => 'Transporte', 'color' => '#2563eb', 'icon' => 'car'],
            ['name' => 'Vivienda', 'color' => '#9333ea', 'icon' => 'home'],
            ['name' => 'Servicios', 'color' => '#0891b2', 'icon' => 'receipt'],
            ['name' => 'Educacion', 'color' => '#4f46e5', 'icon' => 'book'],
            ['name' => 'Salud', 'color' => '#dc2626', 'icon' => 'heart'],
            ['name' => 'Ocio', 'color' => '#ea580c', 'icon' => 'sparkles'],
            ['name' => 'Compras', 'color' => '#db2777', 'icon' => 'shopping-bag'],
            ['name' => 'Viajes', 'color' => '#0d9488', 'icon' => 'plane'],
            ['name' => 'Otros', 'color' => '#64748b', 'icon' => 'tag'],
        ])->each(fn (array $category) => Category::create([
            ...$category,
            'user_id' => $user->id,
            'type' => 'personal',
        ]));
    }
}
