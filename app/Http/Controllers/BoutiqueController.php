<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\BoutiqueItem;
use App\Models\Personnage;
use App\Services\BoutiqueService;
use App\Exceptions\BoutiqueException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BoutiqueController extends Controller
{
    public function __construct(private BoutiqueService $boutiqueService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $boutiques = Boutique::active()
            ->with(['boutiqueItems.objet'])
            ->paginate(12);

        return view('boutiques.index', compact('boutiques'));
    }

    public function show(Boutique $boutique)
    {
        if (!$boutique->is_active) {
            abort(404, 'Cette boutique est fermée.');
        }

        $boutiqueItems = $boutique->boutiqueItems()
            ->availableForPurchase()
            ->with('objet')
            ->paginate(20);

        $personnage = Auth::user()->personnages()->where('is_active', true)->first();

        return view('boutiques.show', compact('boutique', 'boutiqueItems', 'personnage'));
    }

    public function purchase(Request $request, Boutique $boutique, BoutiqueItem $boutiqueItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        $personnage = Auth::user()->personnages()->where('is_active', true)->first();
        
        if (!$personnage) {
            return response()->json([
                'error' => 'Aucun personnage actif trouvé.'
            ], 400);
        }

        try {
            $achatHistorique = $this->boutiqueService->purchaseItem(
                $personnage,
                $boutique,
                $boutiqueItem,
                $request->integer('quantity'),
                $request->ip()
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Achat effectué avec succès.',
                    'achat' => [
                        'id' => $achatHistorique->id,
                        'total_price' => $achatHistorique->total_price,
                        'quantity' => $achatHistorique->qty
                    ],
                    'personnage' => [
                        'gold' => $personnage->fresh()->gold
                    ],
                    'stock' => $boutiqueItem->fresh()->stock
                ]);
            }

            return redirect()->route('boutiques.show', $boutique)
                ->with('success', 'Achat effectué avec succès!');

        } catch (BoutiqueException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }

            return redirect()->back()
                ->withErrors(['purchase' => $e->getMessage()])
                ->withInput();
        }
    }

    public function sell(Request $request, Boutique $boutique)
    {
        $request->validate([
            'objet_id' => 'required|exists:objets,id',
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        $personnage = Auth::user()->personnages()->where('is_active', true)->first();
        
        if (!$personnage) {
            return response()->json([
                'error' => 'Aucun personnage actif trouvé.'
            ], 400);
        }

        $objet = \App\Models\Objet::findOrFail($request->objet_id);

        try {
            $achatHistorique = $this->boutiqueService->sellItem(
                $personnage,
                $boutique,
                $objet,
                $request->integer('quantity'),
                $request->ip()
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vente effectuée avec succès.',
                    'vente' => [
                        'id' => $achatHistorique->id,
                        'total_price' => $achatHistorique->total_price,
                        'quantity' => $achatHistorique->qty
                    ],
                    'personnage' => [
                        'gold' => $personnage->fresh()->gold
                    ]
                ]);
            }

            return redirect()->route('boutiques.show', $boutique)
                ->with('success', 'Vente effectuée avec succès!');

        } catch (BoutiqueException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 400);
            }

            return redirect()->back()
                ->withErrors(['sell' => $e->getMessage()])
                ->withInput();
        }
    }

    public function getPrice(Request $request, Boutique $boutique, BoutiqueItem $boutiqueItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'type' => 'required|in:buy,sell'
        ]);

        $personnage = Auth::user()->personnages()->where('is_active', true)->first();
        $quantity = $request->integer('quantity');
        $type = $request->string('type');

        try {
            $unitPrice = $boutiqueItem->calculateFinalPrice($type, $personnage);
            $totalPrice = $unitPrice * $quantity;

            return response()->json([
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'quantity' => $quantity,
                'can_afford' => $personnage ? $personnage->gold >= $totalPrice : false,
                'stock_available' => $type === 'buy' ? $boutiqueItem->stock >= $quantity : true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Impossible de calculer le prix.'
            ], 400);
        }
    }

    public function history(Request $request)
    {
        $personnage = Auth::user()->personnages()->where('is_active', true)->first();
        
        if (!$personnage) {
            return redirect()->route('personnages.index')
                ->withErrors(['error' => 'Aucun personnage actif trouvé.']);
        }

        $historiques = $personnage->achatHistoriques()
            ->with(['boutique', 'objet'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('boutiques.history', compact('historiques', 'personnage'));
    }

    public function sellableItems(Boutique $boutique)
    {
        $personnage = Auth::user()->personnages()->where('is_active', true)->first();
        
        if (!$personnage) {
            return response()->json([
                'error' => 'Aucun personnage actif trouvé.'
            ], 400);
        }

        // Récupérer les objets de l'inventaire que cette boutique accepte
        $inventaireItems = $personnage->inventaireItems()
            ->where('is_equipped', false)
            ->with('objet')
            ->get()
            ->filter(function ($item) use ($boutique) {
                // Vérifier si la boutique accepte cet objet
                $boutiqueItem = BoutiqueItem::where('boutique_id', $boutique->id)
                    ->where('objet_id', $item->objet_id)
                    ->where('allow_sell', true)
                    ->first();
                
                return $boutiqueItem && $boutique->isObjectAllowed($item->objet);
            })
            ->map(function ($item) use ($boutique) {
                $boutiqueItem = BoutiqueItem::where('boutique_id', $boutique->id)
                    ->where('objet_id', $item->objet_id)
                    ->first();
                
                return [
                    'inventaire_item' => $item,
                    'objet' => $item->objet,
                    'sell_price' => $boutiqueItem->calculateFinalPrice('sell'),
                    'max_quantity' => $item->quantity
                ];
            });

        return response()->json($inventaireItems->values());
    }
}