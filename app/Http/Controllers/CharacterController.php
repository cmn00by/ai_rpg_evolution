<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Personnage;
use App\Models\Classe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CharacterController extends Controller
{
    /**
     * Affiche la page de sélection de personnage
     */
    public function select()
    {
        $user = Auth::user();
        $characters = $user->personnages()->with('classe')->get();
        
        return view('character.select', compact('characters'));
    }

    /**
     * Définit le personnage actif
     */
    public function setActive(Request $request, Personnage $character)
    {
        $user = Auth::user();
        
        // Vérifier que le personnage appartient à l'utilisateur
        if ($character->user_id !== $user->id) {
            abort(403, 'Ce personnage ne vous appartient pas.');
        }
        
        $user->update(['active_character_id' => $character->id]);
        
        return redirect()->intended(route('dashboard'))
            ->with('success', "Personnage {$character->nom} sélectionné.");
    }

    /**
     * Affiche le formulaire de création de personnage
     */
    public function create()
    {
        $classes = Classe::all();
        return view('character.create', compact('classes'));
    }

    /**
     * Crée un nouveau personnage
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Limiter le nombre de personnages par utilisateur
        if ($user->personnages()->count() >= 5) {
            return back()->with('error', 'Vous ne pouvez pas avoir plus de 5 personnages.');
        }
        
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:personnages,nom'],
            'classe_id' => ['required', 'exists:classes,id'],
        ]);
        
        $character = $user->personnages()->create([
            'nom' => $validated['nom'],
            'classe_id' => $validated['classe_id'],
            'niveau' => 1,
            'experience' => 0,
            'gold' => 1000, // Gold de départ
            'is_active' => true,
        ]);
        
        // Si c'est le premier personnage, le définir comme actif
        if ($user->personnages()->count() === 1) {
            $user->update(['active_character_id' => $character->id]);
        }
        
        return redirect()->route('character.select')
            ->with('success', "Personnage {$character->nom} créé avec succès.");
    }

    /**
     * Affiche les détails d'un personnage
     */
    public function show(Personnage $character)
    {
        $user = Auth::user();
        
        // Vérifier que le personnage appartient à l'utilisateur ou que l'utilisateur est admin
        if ($character->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Vous n\'avez pas accès à ce personnage.');
        }
        
        $character->load(['classe', 'inventaire.items.objet']);
        
        return view('character.show', compact('character'));
    }
}
