<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\AchatHistorique;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PlayerHistoryController extends Controller
{
    /**
     * Affiche l'historique des achats/ventes du joueur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        $query = AchatHistorique::where('personnage_id', $activeCharacter->id)
            ->with(['boutique', 'objet.rareteObjet']);

        // Filtres
        if ($request->filled('type')) {
            $query->where('type_transaction', $request->type);
        }

        if ($request->filled('boutique')) {
            $query->where('boutique_id', $request->boutique);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('prix_total', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('prix_total', '<=', $request->max_amount);
        }

        $history = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques
        $stats = $this->getHistoryStats($activeCharacter);

        // Données pour les filtres
        $boutiques = Boutique::orderBy('name')->get();

        return view('player.history.index', compact(
            'history',
            'activeCharacter',
            'boutiques',
            'stats'
        ));
    }

    /**
     * Affiche les détails d'une transaction
     */
    public function show(AchatHistorique $transaction)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        // Vérifier que la transaction appartient au personnage actif
        if ($transaction->personnage_id !== $activeCharacter->id) {
            abort(403, 'Cette transaction ne vous appartient pas.');
        }

        $transaction->load(['boutique', 'objet.rareteObjet', 'objet.slotEquipement']);
        
        // Décoder les métadonnées
        $metadata = json_decode($transaction->meta_json, true) ?? [];

        return view('player.history.show', compact('transaction', 'activeCharacter', 'metadata'));
    }

    /**
     * Récupère les statistiques de l'historique
     */
    private function getHistoryStats($character)
    {
        $baseQuery = AchatHistorique::where('personnage_id', $character->id);
        
        return [
            'total_transactions' => $baseQuery->count(),
            'total_spent' => $baseQuery->where('type_transaction', 'achat')->sum('prix_total'),
            'total_earned' => $baseQuery->where('type_transaction', 'vente')->sum('prix_total'),
            'total_purchases' => $baseQuery->where('type_transaction', 'achat')->count(),
            'total_sales' => $baseQuery->where('type_transaction', 'vente')->count(),
            'this_month_spent' => $baseQuery->where('type_transaction', 'achat')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('prix_total'),
            'this_month_earned' => $baseQuery->where('type_transaction', 'vente')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('prix_total'),
            'favorite_shop' => $this->getFavoriteShop($character),
            'most_bought_item' => $this->getMostBoughtItem($character)
        ];
    }

    /**
     * Récupère la boutique favorite
     */
    private function getFavoriteShop($character)
    {
        return AchatHistorique::where('personnage_id', $character->id)
            ->select('boutique_id')
            ->selectRaw('COUNT(*) as transaction_count')
            ->with('boutique')
            ->groupBy('boutique_id')
            ->orderBy('transaction_count', 'desc')
            ->first();
    }

    /**
     * Récupère l'objet le plus acheté
     */
    private function getMostBoughtItem($character)
    {
        return AchatHistorique::where('personnage_id', $character->id)
            ->where('type_transaction', 'achat')
            ->select('objet_id')
            ->selectRaw('SUM(quantite) as total_quantity')
            ->with('objet')
            ->groupBy('objet_id')
            ->orderBy('total_quantity', 'desc')
            ->first();
    }

    /**
     * Exporte l'historique en CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $activeCharacter = $user->activeCharacter;
        
        if (!$activeCharacter) {
            return redirect()->route('character.select')
                ->with('error', 'Veuillez sélectionner un personnage actif.');
        }

        $query = AchatHistorique::where('personnage_id', $activeCharacter->id)
            ->with(['boutique', 'objet'])
            ->orderBy('created_at', 'desc');

        // Appliquer les mêmes filtres que l'index
        if ($request->filled('type')) {
            $query->where('type_transaction', $request->type);
        }

        if ($request->filled('boutique')) {
            $query->where('boutique_id', $request->boutique);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        $filename = "historique_{$activeCharacter->name}_" . Carbon::now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'Date',
                'Type',
                'Boutique',
                'Objet',
                'Quantité',
                'Prix unitaire',
                'Prix total'
            ]);

            // Données
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    ucfirst($transaction->type_transaction),
                    $transaction->boutique->name,
                    $transaction->objet->name,
                    $transaction->quantite,
                    $transaction->prix_unitaire,
                    $transaction->prix_total
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}