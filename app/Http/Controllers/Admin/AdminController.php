<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Personnage;
use App\Models\Boutique;
use App\Models\AchatHistorique;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Tableau de bord administrateur
     */
    public function dashboard()
    {
        try {
            $stats = [
                'users' => User::count(),
                'characters' => Personnage::count(),
                'shops' => Boutique::count(),
                'purchases_today' => AchatHistorique::count(),
            ];
        } catch (\Exception $e) {
            $stats = [
                'users' => 0,
                'characters' => 0,
                'shops' => 0,
                'purchases_today' => 0,
            ];
        }
        
        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Gestion des utilisateurs
     */
    public function users()
    {
        $users = User::with('roles')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Gestion des personnages
     */
    public function characters()
    {
        $characters = Personnage::with(['user', 'classe'])->paginate(20);
        return view('admin.characters.index', compact('characters'));
    }

    /**
     * Gestion des boutiques
     */
    public function shops()
    {
        $shops = Boutique::with('items.objet')->paginate(20);
        return view('admin.shops.index', compact('shops'));
    }

    /**
     * Historique des achats
     */
    public function purchases()
    {
        $purchases = AchatHistorique::with(['personnage.user', 'boutique'])
            ->latest()->paginate(20);
        return view('admin.purchases.index', compact('purchases'));
    }
}
