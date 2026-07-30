<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function view(User $user, Document $document): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function update(User $user, Document $document): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }

    public function delete(User $user, Document $document): bool
    {
        return in_array($user->role->value, ['admin', 'moderator']);
    }
}
