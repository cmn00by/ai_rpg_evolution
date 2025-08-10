<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Personnage;

class EnsureActiveCharacter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Vérifier si l'utilisateur a un personnage actif en session
        $activeCharacterId = session('active_character_id');
        
        if (!$activeCharacterId) {
            return redirect()->route('characters.index')
                ->with('warning', 'Vous devez sélectionner un personnage pour accéder à cette fonctionnalité.');
        }

        // Vérifier que le personnage actif appartient bien à l'utilisateur
        $activeCharacter = Personnage::where('id', $activeCharacterId)
            ->where('user_id', $user->id)
            ->first();

        if (!$activeCharacter) {
            // Le personnage actif n'existe plus ou n'appartient pas à l'utilisateur
            session()->forget('active_character_id');
            return redirect()->route('characters.index')
                ->with('error', 'Le personnage sélectionné n\'est plus disponible.');
        }

        // Injecter le personnage actif dans la requête
        $request->merge(['active_character' => $activeCharacter]);
        app()->instance('active_character', $activeCharacter);

        return $next($request);
    }
}
