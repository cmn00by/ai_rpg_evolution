<?php

namespace Tests\Feature;

use App\Events\AttributeDeleted;
use App\Events\AttributeUpdated;
use App\Jobs\RecalculateCharacterStatsBatch;
use App\Models\Attribut;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\PersonnageAttributsCache;
use App\Models\User;
use App\Services\CharacterStatsCacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Classe $classe;
    private Personnage $personnage;
    private Attribut $forceAttribute;
    private Attribut $vigueurAttribute;
    private CharacterStatsCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // Créer une classe de test
        $this->classe = Classe::create([
            'name' => 'Guerrier',
            'slug' => 'guerrier',
            'description' => 'Classe de guerrier pour les tests'
        ]);

        // Créer des attributs de test
        $this->forceAttribute = Attribut::create([
            'name' => 'Force',
            'slug' => 'force',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true
        ]);

        $this->vigueurAttribute = Attribut::create([
            'name' => 'Vigueur',
            'slug' => 'vigueur',
            'type' => 'int',
            'default_value' => 10,
            'min_value' => 1,
            'max_value' => 100,
            'is_visible' => true
        ]);

        // Associer les attributs à la classe
        $this->classe->attributs()->attach($this->forceAttribute->id, ['base_value' => 15]);
        $this->classe->attributs()->attach($this->vigueurAttribute->id, ['base_value' => 12]);

        // Créer un personnage de test
        $this->personnage = Personnage::create([
            'user_id' => $this->user->id,
            'classe_id' => $this->classe->id,
            'name' => 'Test Character',
            'level' => 1
        ]);

        // Initialiser le gestionnaire de cache
        $this->cacheManager = new CharacterStatsCacheManager();

        // Créer des entrées de cache initiales
        PersonnageAttributsCache::create([
            'personnage_id' => $this->personnage->id,
            'attribut_id' => $this->forceAttribute->id,
            'final_value' => 15,
            'needs_recalculation' => false,
            'calculated_at' => now()
        ]);

        PersonnageAttributsCache::create([
            'personnage_id' => $this->personnage->id,
            'attribut_id' => $this->vigueurAttribute->id,
            'final_value' => 12,
            'needs_recalculation' => false,
            'calculated_at' => now()
        ]);
    }

    /** @test */
    public function it_invalidates_cache_when_attribute_default_value_changes()
    {
        Event::fake();
        Queue::fake();

        // Vérifier que le cache est initialement valide
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        $this->assertFalse($cacheEntry->needs_recalculation);

        // Modifier la valeur par défaut de l'attribut
        $this->forceAttribute->update(['default_value' => 12]);

        // Déclencher l'événement
        Event::dispatch(new AttributeUpdated($this->forceAttribute, ['default_value' => 10], ['default_value' => 12]));

        // Vérifier que le cache a été invalidé
        $cacheEntry->refresh();
        $this->assertTrue($cacheEntry->needs_recalculation);

        // Vérifier qu'un job de recalcul a été dispatché
        Queue::assertPushed(RecalculateCharacterStatsBatch::class);
    }

    /** @test */
    public function it_invalidates_cache_when_attribute_min_max_changes()
    {
        Event::fake();
        Queue::fake();

        // Modifier les valeurs min/max
        $this->forceAttribute->update([
            'min_value' => 5,
            'max_value' => 50
        ]);

        // Déclencher l'événement
        Event::dispatch(new AttributeUpdated(
            $this->forceAttribute,
            ['min_value' => 1, 'max_value' => 100],
            ['min_value' => 5, 'max_value' => 50]
        ));

        // Vérifier que le cache a été invalidé
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        $this->assertTrue($cacheEntry->needs_recalculation);

        Queue::assertPushed(RecalculateCharacterStatsBatch::class);
    }

    /** @test */
    public function it_does_not_invalidate_cache_for_irrelevant_attribute_changes()
    {
        Event::fake();
        Queue::fake();

        // Modifier un champ qui n'affecte pas le calcul
        $this->forceAttribute->update(['description' => 'Nouvelle description']);

        // Déclencher l'événement
        Event::dispatch(new AttributeUpdated(
            $this->forceAttribute,
            ['description' => 'Ancienne description'],
            ['description' => 'Nouvelle description']
        ));

        // Vérifier que le cache n'a pas été invalidé
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        $this->assertFalse($cacheEntry->needs_recalculation);

        Queue::assertNotPushed(RecalculateCharacterStatsBatch::class);
    }

    /** @test */
    public function it_cleans_up_cache_when_attribute_is_deleted()
    {
        Event::fake();

        // Vérifier que l'entrée de cache existe
        $this->assertTrue(
            PersonnageAttributsCache::where('attribut_id', $this->forceAttribute->id)->exists()
        );

        // Déclencher l'événement de suppression
        Event::dispatch(new AttributeDeleted($this->forceAttribute));

        // Vérifier que l'entrée de cache a été supprimée
        $this->assertFalse(
            PersonnageAttributsCache::where('attribut_id', $this->forceAttribute->id)->exists()
        );
    }

    /** @test */
    public function it_invalidates_cache_when_character_class_changes()
    {
        // Créer une nouvelle classe
        $nouvelleClasse = Classe::create([
            'name' => 'Mage',
            'slug' => 'mage',
            'description' => 'Classe de mage'
        ]);

        // Associer les attributs avec des valeurs différentes
        $nouvelleClasse->attributs()->attach($this->forceAttribute->id, ['base_value' => 8]);
        $nouvelleClasse->attributs()->attach($this->vigueurAttribute->id, ['base_value' => 10]);

        // Changer la classe du personnage
        $this->personnage->update(['classe_id' => $nouvelleClasse->id]);

        // Utiliser le gestionnaire de cache pour invalider
        $this->cacheManager->invalidateAllCache($this->personnage->id, 'class_changed');

        // Vérifier que tout le cache du personnage a été invalidé
        $invalidatedEntries = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('needs_recalculation', true)
            ->count();
        
        $this->assertEquals(2, $invalidatedEntries);

        // Nettoyer
        $nouvelleClasse->attributs()->detach();
        $nouvelleClasse->delete();
    }

    /** @test */
    public function it_invalidates_cache_when_class_attribute_pivot_changes()
    {
        Queue::fake();

        // Modifier la valeur de base de l'attribut pour la classe
        $this->classe->attributs()->updateExistingPivot($this->forceAttribute->id, ['base_value' => 20]);

        // Invalider le cache pour cet attribut sur tous les personnages de cette classe
        $this->cacheManager->invalidateAttributeCache($this->forceAttribute->id, 'class_attribute_changed');

        // Vérifier que le cache a été invalidé
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        $this->assertTrue($cacheEntry->needs_recalculation);
    }

    /** @test */
    public function it_invalidates_cache_when_personnage_attribute_override_changes()
    {
        // Ajouter un override personnage
        $this->personnage->attributs()->attach($this->forceAttribute->id, ['override_value' => 5]);

        // Invalider le cache pour ce personnage et cet attribut
        $this->cacheManager->invalidateCache($this->personnage->id, $this->forceAttribute->id, 'personnage_override_changed');

        // Vérifier que le cache a été invalidé
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        $this->assertTrue($cacheEntry->needs_recalculation);

        // Recalculer immédiatement
        $this->cacheManager->recalculateAndCache($this->personnage->id, $this->forceAttribute->id);

        // Vérifier que la nouvelle valeur a été calculée
        $cacheEntry->refresh();
        $this->assertFalse($cacheEntry->needs_recalculation);
        $this->assertEquals(20, $cacheEntry->final_value); // 15 (base) + 5 (override)
    }

    /** @test */
    public function it_handles_batch_invalidation_efficiently()
    {
        Queue::fake();

        // Créer plusieurs personnages avec le même attribut
        $personnages = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $personnage = Personnage::create([
                'user_id' => $user->id,
                'classe_id' => $this->classe->id,
                'name' => "Character {$i}",
                'level' => 1
            ]);
            $personnages[] = $personnage;

            // Créer une entrée de cache
            PersonnageAttributsCache::create([
                'personnage_id' => $personnage->id,
                'attribut_id' => $this->forceAttribute->id,
                'final_value' => 15,
                'needs_recalculation' => false,
                'calculated_at' => now()
            ]);
        }

        // Invalider le cache pour l'attribut sur tous les personnages
        $this->cacheManager->invalidateAttributeCache($this->forceAttribute->id, 'mass_invalidation');

        // Vérifier que tous les caches ont été invalidés
        $invalidatedCount = PersonnageAttributsCache::where('attribut_id', $this->forceAttribute->id)
            ->where('needs_recalculation', true)
            ->count();
        
        $this->assertEquals(6, $invalidatedCount); // 5 nouveaux + 1 original

        // Déclencher un recalcul en batch
        $this->cacheManager->triggerBatchRecalculation($this->forceAttribute->id, 'mass_recalculation');

        // Vérifier qu'un job de batch a été dispatché
        Queue::assertPushed(RecalculateCharacterStatsBatch::class);

        // Nettoyer
        foreach ($personnages as $personnage) {
            $personnage->delete();
            $personnage->user->delete();
        }
    }

    /** @test */
    public function it_tracks_invalidation_reasons()
    {
        // Invalider avec une raison spécifique
        $this->cacheManager->invalidateCache(
            $this->personnage->id,
            $this->forceAttribute->id,
            'equipment_changed'
        );

        // Vérifier que l'entrée a été mise à jour
        $cacheEntry = PersonnageAttributsCache::where('personnage_id', $this->personnage->id)
            ->where('attribut_id', $this->forceAttribute->id)
            ->first();
        
        $this->assertTrue($cacheEntry->needs_recalculation);
        $this->assertNotNull($cacheEntry->updated_at);
    }

    /** @test */
    public function it_handles_cache_cleanup_for_deleted_characters()
    {
        // Vérifier que les entrées de cache existent
        $this->assertEquals(2, PersonnageAttributsCache::where('personnage_id', $this->personnage->id)->count());

        // Supprimer le personnage
        $personnageId = $this->personnage->id;
        $this->personnage->delete();

        // Nettoyer le cache
        $deletedCount = PersonnageAttributsCache::cleanupDeletedPersonnages();

        // Vérifier que les entrées ont été supprimées
        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(0, PersonnageAttributsCache::where('personnage_id', $personnageId)->count());
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
        PersonnageAttributsCache::where('personnage_id', $this->personnage->id)->delete();
        
        if ($this->personnage->exists) {
            $this->personnage->attributs()->detach();
            $this->personnage->delete();
        }
        
        $this->classe->attributs()->detach();
        $this->classe->delete();
        $this->forceAttribute->delete();
        $this->vigueurAttribute->delete();
        $this->user->delete();

        parent::tearDown();
    }
}