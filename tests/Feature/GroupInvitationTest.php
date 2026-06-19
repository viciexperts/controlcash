<?php

namespace Tests\Feature;

use App\Models\ExpenseGroup;
use App\Models\User;
use App\Notifications\GroupInvitationCreated;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GroupInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_unregistered_email_to_group(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $group = ExpenseGroup::create([
            'created_by' => $owner->id,
            'name' => 'Familia',
        ]);
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($owner)
            ->post(route('groups.members.store', $group), [
                'email' => 'nuevo@servidor.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $group->id,
            'invited_by' => $owner->id,
            'email' => 'nuevo@servidor.com',
            'accepted_at' => null,
        ]);

        Notification::assertSentOnDemand(GroupInvitationCreated::class);
    }

    public function test_invited_email_joins_group_after_registering(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $group = ExpenseGroup::create([
            'created_by' => $owner->id,
            'name' => 'Familia',
        ]);
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($owner)
            ->post(route('groups.members.store', $group), [
                'email' => 'nuevo@servidor.com',
            ]);

        $this->post('/logout');

        $this
            ->post('/register', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@servidor.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'nuevo@servidor.com')->firstOrFail();

        $this->assertTrue($group->members()->where('users.id', $user->id)->exists());
        $this->assertDatabaseMissing('group_invitations', [
            'email' => 'nuevo@servidor.com',
            'accepted_at' => null,
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_group_member_email_must_be_valid(): void
    {
        $owner = User::factory()->create();
        $group = ExpenseGroup::create([
            'created_by' => $owner->id,
            'name' => 'Familia',
        ]);
        $group->members()->attach($owner->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this
            ->actingAs($owner)
            ->from(route('groups.show', $group))
            ->post(route('groups.members.store', $group), [
                'email' => 'correo-invalido',
            ])
            ->assertSessionHasErrors('email')
            ->assertRedirect(route('groups.show', $group));
    }
}
