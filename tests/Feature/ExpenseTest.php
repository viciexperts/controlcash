<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExpenseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_expenses_are_visible_to_group_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $category = Category::create([
            'user_id' => $owner->id,
            'name' => 'Ocio',
            'color' => '#10b981',
            'icon' => 'smile',
        ]);
        $group = ExpenseGroup::create([
            'created_by' => $owner->id,
            'name' => 'Apartamento',
        ]);
        $group->members()->attach([
            $owner->id => ['role' => 'admin', 'status' => 'active', 'joined_at' => now()],
            $member->id => ['role' => 'member', 'status' => 'active', 'joined_at' => now()],
        ]);

        $this
            ->actingAs($owner)
            ->post('/expenses', [
                'description' => 'Cena compartida',
                'amount' => 1200,
                'expense_date' => now()->toDateString(),
                'category_id' => $category->id,
                'group_id' => $group->id,
                'paid_by_user_id' => $owner->id,
                'split_type' => 'equal',
                'participant_ids' => [$owner->id, $member->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this
            ->actingAs($member)
            ->get('/expenses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Index')
                ->has('expenses', 1)
                ->where('expenses.0.description', 'Cena compartida')
                ->where('expenses.0.group.name', 'Apartamento')
            );
    }
}
