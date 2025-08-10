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
7. Calculs & Cache
8. Procédure Git/GitHub
9. Commandes utiles
10. Tests
11. Roadmap
12. Licence

---

## 🔎 À propos
Ce projet implémente un **système RPG flexible et scalable**, où :
- Les **attributs** sont centralisés et se propagent automatiquement aux classes et personnages.
- Les **classes** possèdent des stats de base.
- Les **personnages** héritent des stats de leur classe + modificateurs.
- Les **objets/équipements** appliquent bonus/malus dynamiques.
- L’**économie** gère boutiques, réapprovisionnement et transactions.

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

### Module 2 — Raretés · Slots · Objets · Inventaire (Étape 6)
**Objectif :** gérer objets/équipements et inventaires, avec bonus/malus d’attributs.

**Schéma** (tables principales) :
- `raretes_objets` : `name`, `slug` (unique), `order`, `color_hex`, `multiplier`.
- `slots_equipement` : `name`, `slug` (unique), `max_per_slot`.
- `objets` : `name`, `slug` (unique), `rarete_id`, `slot_id`, `stackable` (bool), `base_durability` (nullable), `buy_price`, `sell_price`.
- `objet_attributs` : `objet_id`, `attribut_id`, `modifier_type` (`flat|percent`), `modifier_value` (PK composite recommandé).
- `inventaires` : 1–1 avec `personnages`.
- `inventaire_items` : `inventaire_id`, `objet_id`, `quantity`, `durability` (nullable), `is_equipped` (bool), timestamps.

**Règles métier :**
- **Équipement** : respecter `max_per_slot`. Les % s’additionnent (10% + 15% = 25%). Les **flats s’appliquent avant %**.
- **Stackable** : fusion en inventaire si non équipé ; pour équiper une unité, dé‑stacker au besoin.
- **Durabilité** : si 0 ⇒ auto‑déséquipement (optionnel), évènement `ItemBroken`.
- **Intégrité** : toutes les opérations d’inventaire/équipement en **transaction** ; verrous sur la ligne cible si concurrence.

**Events conseillés :**
`ItemEquipped`, `ItemUnequipped`, `ItemBroken`, `InventoryMerged`. Chaque listener invalide/recalcule **uniquement** les attributs impactés.

**Seeds conseillés :**
- Raretés : Common, Rare, Epic, Legendary.
- Slots : Tête, Torse, Arme, Anneau (max 2), Bottes.
- Objets : quelques épées/anneaux/armures avec 1–2 modificateurs.

### Module 3 — Économie
- Boutiques configurables (slots/raretés autorisés, stock & restock).
- Historique des achats, transactions atomiques.

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
- `php artisan migrate`
- `php artisan migrate:fresh --seed`
- `php artisan queue:work`
- `php artisan tinker`

---

## 🧪 Tests
- Framework : PestPHP
- Lancer les tests : `php artisan test`

---

## 🚀 Roadmap
- [x] Module 1 — migrations, modèles, seeders
- [x] Observers/Events sync attributs
- [x] Calcul stats finales + cache
- [ ] Module 2 — Objets/Équipements (en cours)
- [ ] Module 3 — Économie/Boutiques
- [ ] Back‑office admin (Filament)
- [ ] API publique (REST/GraphQL)
- [ ] Authentification & sécurité avancée

---

## 📄 Licence
MIT
