<?php

namespace App\Http\Controllers;

use App\Models\ExpenseGroup;
use App\Models\GroupInvitation;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\GroupInvitationCreated;
use App\Support\GroupBalances;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Groups/Index', [
            'groups' => $user
                ->groups()
                ->withCount(['members', 'expenses'])
                ->orderBy('name')
                ->get()
                ->map(fn ($group) => [
                    ...$group->toArray(),
                    'members_count' => $group->members_count,
                    'expenses_count' => $group->expenses_count,
                    'is_creator' => $group->created_by === $user->id,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        $group = ExpenseGroup::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $group->members()->attach($request->user()->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group);
    }

    public function show(Request $request, ExpenseGroup $group)
    {
        $this->authorizeMember($request, $group);

        $group->load([
            'members:id,name,email,avatar',
            'expenses.category',
            'expenses.payer:id,name,email',
            'expenses.approver:id,name,email',
            'expenses.splits.user:id,name,email',
            'expenses.comments.user:id,name,email',
            'settlements.fromUser:id,name,email',
            'settlements.toUser:id,name,email',
        ]);

        return Inertia::render('Groups/Show', [
            'group' => $group,
            'balances' => GroupBalances::calculate($group),
            'isAdmin' => $group->members()
                ->where('users.id', $request->user()->id)
                ->wherePivot('role', 'admin')
                ->exists(),
            'isCreator' => $group->created_by === $request->user()->id,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, ExpenseGroup $group)
    {
        $this->authorizeAdmin($request, $group);

        $group->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
        ]));

        return back();
    }

    public function destroy(Request $request, ExpenseGroup $group)
    {
        $this->authorizeCreator($request, $group);
        $group->delete();

        return redirect()->route('groups.index');
    }

    public function addMember(Request $request, ExpenseGroup $group)
    {
        $this->authorizeAdmin($request, $group);

        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($data['email']);
        $user = User::where('email', $email)->first();

        if (! $user) {
            $invitation = GroupInvitation::updateOrCreate(
                [
                    'group_id' => $group->id,
                    'email' => $email,
                ],
                [
                    'invited_by' => $request->user()->id,
                    'accepted_at' => null,
                ],
            );

            Notification::route('mail', $email)->notify(new GroupInvitationCreated($invitation));

            return back();
        }

        if (! $group->members()->where('users.id', $user->id)->exists()) {
            $group->members()->attach($user->id, [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        return back();
    }

    public function removeMember(Request $request, ExpenseGroup $group, User $user)
    {
        $this->authorizeAdmin($request, $group);
        abort_if($group->created_by === $user->id, 422);

        $group->members()->detach($user->id);

        return back();
    }

    public function settle(Request $request, ExpenseGroup $group)
    {
        $this->authorizeMember($request, $group);

        $data = $request->validate([
            'from_user_id' => ['required', 'exists:users,id'],
            'to_user_id' => ['required', 'exists:users,id', 'different:from_user_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'settled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $memberIds = $group->members()->pluck('users.id')->all();
        abort_unless(in_array((int) $data['from_user_id'], $memberIds, true), 422);
        abort_unless(in_array((int) $data['to_user_id'], $memberIds, true), 422);

        Settlement::create([
            ...$data,
            'group_id' => $group->id,
        ]);

        return back();
    }

    private function authorizeMember(Request $request, ExpenseGroup $group): void
    {
        abort_unless($group->members()->where('users.id', $request->user()->id)->exists(), 403);
    }

    private function authorizeAdmin(Request $request, ExpenseGroup $group): void
    {
        abort_unless(
            $group->members()
                ->where('users.id', $request->user()->id)
                ->wherePivot('role', 'admin')
                ->exists(),
            403,
        );
    }

    private function authorizeCreator(Request $request, ExpenseGroup $group): void
    {
        abort_unless($group->created_by === $request->user()->id, 403);
    }
}
