<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function view(User $user, User $model): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function update(User $user, User $model): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function delete(User $user, User $model): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }
}
