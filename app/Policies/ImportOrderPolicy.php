<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ImportOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportOrderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportOrder');
    }

    public function view(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('View:ImportOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportOrder');
    }

    public function update(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('Update:ImportOrder');
    }

    public function delete(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('Delete:ImportOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ImportOrder');
    }

    public function restore(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('Restore:ImportOrder');
    }

    public function forceDelete(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('ForceDelete:ImportOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportOrder');
    }

    public function replicate(AuthUser $authUser, ImportOrder $importOrder): bool
    {
        return $authUser->can('Replicate:ImportOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportOrder');
    }

}