<?php

namespace App\Policies;

use App\Models\Inventaire;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InventairePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-inventories') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inventaire $inventaire): bool
    {
        // L'utilisateur peut voir l'inventaire s'il appartient à son personnage ou s'il est admin
        return $inventaire->personnage->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-inventories') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inventaire $inventaire): bool
    {
        // L'utilisateur peut modifier l'inventaire s'il appartient à son personnage ou s'il est admin
        return $inventaire->personnage->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inventaire $inventaire): bool
    {
        return $user->hasPermissionTo('manage-inventories') || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inventaire $inventaire): bool
    {
        return $user->hasPermissionTo('manage-inventories') || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inventaire $inventaire): bool
    {
        return $user->hasPermissionTo('manage-inventories') || $user->isAdmin();
    }
}
