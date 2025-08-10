<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Personnage;
use App\Models\InventairePersonnage;
use App\Models\AchatHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PlayerDashboardController extends Controller
{
    /**
     * Affiche le dashboard principal du joueur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        // Stats finales du personnage (TOP 5)
        $topStats = $this->getTopStats($activeCharacter);
        
        // Équipements portés
        $equippedItems = $this->getEquippedItems($activeCharacter);
        
        // Résumé de l'inventaire
        $inventorySummary = $this->getInventorySummary($activeCharacter);
        
        // Événements récents
        $recentEvents = $this->getRecentEvents($activeCharacter);
        
        return view('player.dashboard', compact(
            'activeCharacter',
            'topStats',
            'equippedItems',
            'inventorySummary',
            'recentEvents'
        ));
    }

    /**
     * Récupère les 5 meilleures statistiques du personnage
     */
    private function getTopStats(Personnage $character)
    {
        $cacheKey = "character_top_stats_{$character->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($character) {
            return $character->personnageAttributsCache()
                ->with('attribut')
                ->orderBy('valeur_finale', 'desc')
                ->take(5)
                ->get()
                ->map(function ($stat) {
                    return [
                        'name' => $stat->attribut->name,
                        'value' => $stat->valeur_finale,
                        'base' => $stat->valeur_base,
                        'equipment_bonus' => $stat->valeur_finale - $stat->valeur_base
                    ];
                });
        });
    }

    /**
     * Récupère les objets équipés
     */
    private function getEquippedItems(Personnage $character)
    {
        return InventairePersonnage::where('personnage_id', $character->id)
            ->where('is_equipped', true)
            ->with(['objet.rarete', 'objet.slot'])
            ->get();
    }

    /**
     * Récupère un résumé de l'inventaire
     */
    private function getInventorySummary(Personnage $character)
    {
        $inventory = InventairePersonnage::where('personnage_id', $character->id);
        
        return [
            'total_items' => $inventory->sum('quantite'),
            'equipped_items' => $inventory->where('is_equipped', true)->count(),
            'unique_items' => $inventory->count(),
            'damaged_items' => $inventory->where('durability_current', '<', 100)->count()
        ];
    }

    /**
     * Récupère les événements récents
     */
    private function getRecentEvents(Personnage $character)
    {
        return AchatHistorique::where('personnage_id', $character->id)
            ->with(['boutique', 'objet'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'type' => $event->type_transaction,
                    'description' => $this->formatEventDescription($event),
                    'date' => $event->created_at,
                    'amount' => $event->prix_total
                ];
            });
    }

    /**
     * Formate la description d'un événement
     */
    private function formatEventDescription($event)
    {
        $action = $event->type_transaction === 'achat' ? 'Acheté' : 'Vendu';
        return "{$action} {$event->quantite}x {$event->objet->name} chez {$event->boutique->name}";
    }

    /**
     * Change le personnage actif
     */
    public function switchCharacter(Request $request)
    {
        $request->validate([
            'character_id' => 'required|exists:personnages,id'
        ]);

        $character = Personnage::where('id', $request->character_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        Auth::user()->update(['active_character_id' => $character->id]);

        return redirect()->route('player.dashboard')
            ->with('success', "Personnage actif changé pour {$character->name}");
    }
}