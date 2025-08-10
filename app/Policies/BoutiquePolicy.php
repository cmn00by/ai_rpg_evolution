<?php

namespace App\Policies;

use App\Models\Boutique;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoutiquePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Tout le monde peut voir les boutiques
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Boutique $boutique): bool
    {
        return true; // Tout le monde peut voir une boutique
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-shops') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Boutique $boutique): bool
    {
        return $user->hasPermissionTo('manage-shops') || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Boutique $boutique): bool
    {
        return $user->hasPermissionTo('manage-shops') || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Boutique $boutique): bool
    {
        return $user->hasPermissionTo('manage-shops') || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Boutique $boutique): bool
    {
        return $user->hasPermissionTo('manage-shops') || $user->isAdmin();
    }

    /**
     * Determine whether the user can purchase from the shop.
     */
    public function purchase(User $user, Boutique $boutique): bool
    {
        return $user->hasPermissionTo('trade') && $user->active_character_id;
    }
}
