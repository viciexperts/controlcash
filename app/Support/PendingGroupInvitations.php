<?php

namespace App\Support;

use App\Models\GroupInvitation;
use App\Models\User;

class PendingGroupInvitations
{
    public static function acceptFor(User $user): void
    {
        GroupInvitation::query()
            ->with('group')
            ->where('email', strtolower($user->email))
            ->whereNull('accepted_at')
            ->get()
            ->each(function (GroupInvitation $invitation) use ($user) {
                if (! $invitation->group?->trashed()
                    && ! $invitation->group->members()->where('users.id', $user->id)->exists()) {
                    $invitation->group->members()->attach($user->id, [
                        'role' => 'member',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]);
                }

                $invitation->update(['accepted_at' => now()]);
            });
    }
}
