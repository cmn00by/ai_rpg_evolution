# AI_RPG_Evolution

Projet **RPG modulaire** sous Laravel 11 — gestion dynamique de classes, personnages, attributs, équipements, inventaires et économie in‑game.

---

## 📋 Sommaire
1. À propos
2. Prérequis
3. Installation
4. Configuration
5. Structure du projet
6. Modules
7. Authentification & Autorisation
8. Back-office Admin (Filament)
9. Calculs & Cache
10. Procédure Git/GitHub
11. Commandes utiles
12. Tests
13. Roadmap
14. Licence

---

## 🔎 À propos
Ce projet implémente un **système RPG flexible et scalable**, où :
- Les **attributs** sont centralisés et se propagent automatiquement aux classes et personnages.
- Les **classes** possèdent des stats de base.
- Les **personnages** héritent des stats de leur classe + modificateurs.
- Les **objets/équipements** appliquent bonus/malus dynamiques.
- L'**économie** gère boutiques, réapprovisionnement et transactions.
- L'**authentification** et l'**autorisation** sécurisent l'accès aux fonctionnalités.
- Le **back-office Filament** offre une interface d'administration complète et moderne.

---

## 📦 Prérequis
- PHP ≥ 8.3, Composer ≥ 2.x
- MySQL ≥ 8 (ou MariaDB ≥ 10.6)
- Node.js ≥ 20 (pour back‑office/admin plus tard)
- Git

*(Optionnel : Laravel Sail ou autre conteneur Docker pour environnement isolé)*

---

## ⚙ Installation
```bash
git clone https://github.com/<user>/<repo>.git
cd ai_rpg_evolution
composer install
cp .env.example .env
php artisan key:generate
```

---

## 🔧 Configuration
- Créer la base de données `ai_rpg_evolution` et un utilisateur MySQL dédié.
- Renseigner `.env` :
```env
DB_DATABASE=ai_rpg_evolution
DB_USERNAME=ai_rpg
DB_PASSWORD=strong_password
```
- Lancer les migrations :
```bash
php artisan migrate
```
- Lancer les seeders de base :
```bash
php artisan db:seed
```

### 👥 Comptes par défaut
Le seeder `RolePermissionSeeder` crée automatiquement les comptes suivants :

- **Super Admin** : `superadmin@ai.rpg.com` / `password123`
- **Admin** : `admin@ai.rpg.com` / `password123`
- **Demo Player** : `demo@ai.rpg.com` / `password123`

*Note : Ces comptes sont automatiquement vérifiés (pas besoin de confirmation email).*

---

## 🗂 Structure du projet
- **Module 1** : Classes · Personnages · Attributs
- **Module 2** : Raretés · Slots · Objets · Inventaire
- **Module 3** : Économie · Boutiques

Organisation des répertoires :
```
app/
 ├─ Models/        (Eloquent ORM)
 ├─ Observers/     (Sync auto attributs/classes/personnages)
 ├─ Services/      (Logique métier)
 └─ Events/        (Domain events)

database/
 ├─ migrations/    (schéma SQL)
 ├─ seeders/       (données initiales)
 └─ factories/     (génération données test)
```

---

## 🧩 Modules

### Module 1 — Classes, Personnages, Attributs
- Attributs dynamiques, synchronisation automatique via events/observers.
- Pivots `classe_attributs` et `personnage_attributs`.
- Cache des stats finales dans `personnage_attributs_cache`.

### Module 2 — Raretés · Slots · Objets · Inventaire ✅
**Objectif :** gérer objets/équipements et inventaires, avec bonus/malus d'attributs.

**Schéma** (tables principales) :
- `raretes_objets` : `name`, `slug` (unique), `order`, `color_hex`, `multiplier`.
- `slots_equipement` : `name`, `slug` (unique), `max_per_slot`.
- `objets` : `name`, `slug` (unique), `rarete_id`, `slot_id`, `stackable` (bool), `base_durability` (nullable), `buy_price`, `sell_price`.
- `objet_attributs` : `objet_id`, `attribut_id`, `modifier_type` (`flat|percent`), `modifier_value` (PK composite recommandé).
- `inventaires` : 1–1 avec `personnages`.
- `inventaire_items` : `inventaire_id`, `objet_id`, `quantity`, `durability` (nullable), `is_equipped` (bool), timestamps.

**Règles métier :**
- **Équipement** : respecter `max_per_slot`. Les % s'additionnent (10% + 15% = 25%). Les **flats s'appliquent avant %**.
- **Stackable** : fusion en inventaire si non équipé ; pour équiper une unité, dé‑stacker au besoin.
- **Durabilité** : si 0 ⇒ auto‑déséquipement (optionnel), évènement `ItemBroken`.
- **Intégrité** : toutes les opérations d'inventaire/équipement en **transaction** ; verrous sur la ligne cible si concurrence.

**Events conseillés :**
`ItemEquipped`, `ItemUnequipped`, `ItemBroken`, `InventoryMerged`. Chaque listener invalide/recalcule **uniquement** les attributs impactés.

**Seeds conseillés :**
- Raretés : Common, Rare, Epic, Legendary.
- Slots : Tête, Torse, Arme, Anneau (max 2), Bottes.
- Objets : quelques épées/anneaux/armures avec 1–2 modificateurs.

### Module 3 — Économie ✅
**Objectif :** système économique complet avec boutiques, transactions et historique.

**Schéma** (tables principales) :
- `boutiques` : `name`, `description`, `tax_rate`, `discount_rate`, `max_daily_purchases`, `restock_frequency_hours`, `allowed_raretes`, `allowed_slots`.
- `boutique_items` : `boutique_id`, `objet_id`, `stock_quantity`, `base_price`, `is_active`, timestamps.
- `achat_historiques` : `personnage_id`, `boutique_id`, `objet_id`, `quantity`, `unit_price`, `total_price`, `meta_json`, timestamps.

**Fonctionnalités implémentées :**
- **Système de boutiques** : configuration flexible avec taxes, remises et limites quotidiennes.
- **Gestion des stocks** : réapprovisionnement automatique programmé.
- **Transactions sécurisées** : validation des fonds, gestion des erreurs, transactions atomiques.
- **Historique complet** : tracking détaillé des achats avec métadonnées (soldes avant/après, taxes, remises).
- **Intégration inventaire** : ajout automatique des objets achetés à l'inventaire du personnage.
- **Système de réputation** : influence sur les prix et accès aux boutiques.
- **Tests complets** : 12 tests couvrant tous les cas d'usage et erreurs.

---

## 🔐 Authentification & Autorisation

### Système de rôles et permissions ✅
**Objectif :** sécuriser l'accès aux différentes fonctionnalités selon le profil utilisateur.

**Rôles configurés :**
- **super-admin** : accès complet à toutes les fonctionnalités
- **admin** : gestion des utilisateurs, personnages, objets et boutiques
- **staff** : consultation des logs et modération
- **player** : accès aux fonctionnalités de jeu (personnages, boutiques)

**Permissions par module :**
- **Gestion des attributs** : `manage-attributes` (super-admin, admin)
- **Gestion des classes** : `manage-classes` (super-admin, admin)
- **Gestion des objets** : `manage-items` (super-admin, admin)
- **Gestion des boutiques** : `manage-shops` (super-admin, admin)
- **Gestion des inventaires** : `manage-inventories` (super-admin, admin)
- **Gestion des utilisateurs** : `manage-users` (super-admin, admin)
- **Consultation des logs** : `view-logs` (super-admin, admin, staff)
- **Accès au panel admin** : `view-admin-panel` (super-admin, admin)

**Protection des routes :**
- `/characters/*` : accessible aux utilisateurs avec rôle "player"
- `/admin/*` : accessible uniquement aux utilisateurs avec rôle "admin"
- `/dashboard` : accessible aux utilisateurs authentifiés

### 🎛️ Interfaces d'administration

Le projet propose **deux interfaces d'administration distinctes** :

#### 1. Interface personnalisée (`/admin/dashboard`)
- **Accès** : `/admin/dashboard`
- **Contrôleur** : `AdminController.php`
- **Fonctionnalités** :
  - Tableau de bord avec statistiques générales
  - Gestion basique des utilisateurs
  - Vue d'ensemble des personnages et boutiques
  - Interface simple et légère

#### 2. Interface Filament (`/admin`)
- **Accès** : `/admin`
- **Framework** : Filament (interface moderne)
- **Fonctionnalités** : Interface complète de gestion (voir section dédiée ci-dessous)

---

## 🎛️ Back-office Admin (Filament)

### Interface d'administration complète ✅
**Accès :** `/admin` (réservé aux utilisateurs avec rôle "admin" ou "super-admin")

**Objectif :** Interface d'administration moderne et intuitive basée sur Filament pour gérer tous les aspects du système RPG avec des fonctionnalités CRUD avancées.

**Ressources Filament configurées :**

#### 🏛️ ClasseResource
- **Gestion des classes** : création, édition, suppression des classes de personnages
- **Formulaires** : nom, slug, description, stats de base (force, agilité, intelligence, etc.)
- **Table** : affichage avec recherche, tri et filtres par attributs
- **Relation Manager** : gestion des attributs de classe avec valeurs min/max et types
- **Actions** : duplication de classe, calcul automatique des stats

#### 👤 PersonnageResource
- **Gestion des personnages** : profils complets avec stats calculées
- **Formulaires** : nom, classe, joueur, niveau, or, réputation, statut actif
- **Création manuelle** : tous les champs sont éditables pour création personnalisée
- **Relations** : inventaire, historique d'achats, attributs personnalisés
- **Actions** : reset stats, gestion de l'équipement, calcul des bonus

#### 💎 RareteObjetResource & 🎯 SlotEquipementResource
- **Raretés d'objets** : gestion des niveaux de rareté avec couleurs et multiplicateurs
- **Slots d'équipement** : configuration des emplacements d'équipement et limites
- **Interface** : formulaires simplifiés avec validation et aperçu visuel

#### ⚔️ ObjetResource
- **Gestion des objets** : création d'armes, armures et objets avec attributs
- **Formulaires** : nom, rareté, slot, prix, durabilité, stackable
- **Relations** : modificateurs d'attributs, présence en boutiques
- **Actions** : duplication d'objet, calcul des prix selon la rareté

#### 🏪 BoutiqueResource
- **Gestion des boutiques** : configuration complète des magasins
- **Formulaires** : nom, taxes, remises, limites quotidiennes, fréquence de restock
- **Relation Manager** : gestion des articles en boutique avec stocks et prix
- **Actions** : restock manuel, gestion des stocks, configuration des prix

#### 📊 AchatHistoriqueResource
- **Historique des achats** : consultation en lecture seule des transactions
- **Affichage** : détails complets des achats avec métadonnées
- **Filtres** : par personnage, boutique, type de transaction, période
- **Vue détaillée** : informations complètes sur chaque transaction

#### 🎒 InventairePersonnageResource
- **Gestion des inventaires** : vue d'ensemble des possessions des personnages
- **Formulaires** : ajout/modification d'objets, gestion des quantités
- **Actions** : équiper/déséquiper, réparer, gestion de la durabilité
- **Filtres** : par personnage, rareté, slot d'équipement, statut d'équipement

**Fonctionnalités avancées :**
- **Navigation organisée** : regroupement logique par modules RPG
- **Actions en lot** : opérations sur plusieurs enregistrements simultanément
- **Notifications** : retours utilisateur pour toutes les actions importantes
- **Validation** : contrôles de cohérence et règles métier intégrées
- **Recherche globale** : recherche rapide dans toutes les ressources
- **Filtres intelligents** : filtrage contextuel selon les relations
- **Interface responsive** : adaptation mobile et desktop
- **Relation Managers optimisés** : gestion correcte des relations many-to-many avec pivot
- **Debugging intégré** : outils de débogage pour résoudre les problèmes d'affichage

---

## 📊 Calculs & Cache
**Ordre d’application recommandé :**
```
base (classe)
+ override personnage
+ somme flats équipements
= raw
× (1 + somme % équipements / 100)
+ flats effets temporaires
× (1 + % effets temporaires / 100)
→ arrondi (ex. floor)
→ clamp(min_value, max_value)
```
- Cache persistant dans `personnage_attributs_cache`.
- Invalidation lors de toute modif impactant attributs/classes/persos/inventaire/effets.
- Recalcul **ciblé** si possible, **massif** via queues sinon.

---

## 🌐 Procédure Git/GitHub
> Remplace `<user>` et `<repo>` par tes valeurs.

**Initialisation locale :**
```bash
git init
git add .
git commit -m "chore: initial commit (module 1 + infra tests + readme)"
```

**Création du dépôt distant (GitHub UI)** : crée un dépôt vide puis récupère l’URL HTTPS/SSH.

**Lier et pousser :**
```bash
git remote add origin https://github.com/<user>/<repo>.git
git branch -M main
git push -u origin main
```

**Workflow recommandé :**
```bash
git checkout -b feat/module-2-inventory
# ... dev ...
git commit -m "feat(module-2): migrations + règles inventaire/équipement"
git push -u origin feat/module-2-inventory
# Ouvre une Pull Request vers main
```

---

## 🛠 Commandes utiles
- `php artisan migrate` (exécuter les migrations)
- `php artisan migrate:fresh --seed` (réinitialiser la base avec données de test)
- `php artisan db:seed --class=RolePermissionSeeder` (créer les rôles et comptes par défaut)
- `php artisan queue:work` (traiter les tâches en arrière-plan)
- `php artisan tinker` (console interactive Laravel)
- `php artisan boutiques:restock` (réapprovisionnement manuel des boutiques)
- `php artisan serve --host=0.0.0.0 --port=8000` (démarrer le serveur de développement)

---

## 🧪 Tests
- Framework : PestPHP
- Lancer les tests : `php artisan test`
- **Couverture actuelle :**
  - ✅ **AuthenticationTest** : 7 tests couvrant l'authentification complète
    - Tests de connexion/déconnexion
    - Tests d'inscription utilisateur
    - Tests d'accès aux pages protégées
    - Tests de redirection après authentification
  - ✅ **RoleAuthorizationTest** : 6 tests couvrant l'autorisation par rôles
    - Attribution automatique du rôle "player"
    - Protection des routes admin
    - Accès conditionnel selon les rôles
    - Tests de sécurité et permissions
  - ✅ **BoutiqueTest** : 12 tests couvrant l'ensemble du système économique
    - Tests d'achat avec validation des fonds
    - Tests de gestion des stocks et réapprovisionnement
    - Tests des limites quotidiennes et réputation
    - Tests de l'historique des achats avec métadonnées
    - Tests d'intégration inventaire/boutique
    - Gestion des erreurs et cas limites
  - ✅ **Total : 25 tests passants avec 25 assertions**

---

## 🚀 Roadmap
- [x] Module 1 — migrations, modèles, seeders
- [x] Observers/Events sync attributs
- [x] Calcul stats finales + cache
- [x] Module 2 — Objets/Équipements/Inventaires
- [x] Module 3 — Économie/Boutiques complète
- [x] **Authentification & Autorisation** — système complet de sécurité
- [x] Tests complets (25 tests passants)
- [x] Factories pour tous les modèles
- [x] **Back‑office admin (Filament)** — interface d'administration complète
- [x] **Correction des RelationManagers** — résolution des problèmes d'affichage des attributs
- [x] **Amélioration de l'interface de création** — formulaires entièrement fonctionnels
- [ ] API publique (REST/GraphQL)
- [ ] Système de combat
- [ ] Quêtes et missions
- [ ] Guildes et interactions sociales
- [ ] Système de notifications
- [ ] Interface utilisateur avancée

---

## 📄 Licence
MIT
