<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\Attribut;
use App\Models\Classe;
use App\Models\Personnage;
use App\Models\User;
use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Jobs\RecalculateCharacterStatsBatch;

class AttributeSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer des données de test
        $this->seed();
    }

    public function test_attribute_creation_triggers_synchronization(): void
    {
        // Compter les relations existantes
        $initialClasseAttributsCount = DB::table('classe_attributs')->count();
        $initialPersonnageAttributsCount = DB::table('personnage_attributs')->count();

        // Créer un nouvel attribut
        $attribut = Attribut::create([
            'name' => 'Nouvelle Force',
            'slug' => 'nouvelle-force',
            'type' => 'int',
            'default_value' => 5,
            'min_value' => 0,
            'max_value' => 20,
            'is_visible' => true,
        ]);

        // Traiter les jobs de création
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que l'attribut a été ajouté à toutes les classes
        $classesCount = Classe::count();
        $newClasseAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertEquals($classesCount, $newClasseAttributsCount);

        // Vérifier que l'attribut a été ajouté à tous les personnages (sauf derived)
        $personnagesCount = Personnage::count();
        $newPersonnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertEquals($personnagesCount, $newPersonnageAttributsCount);

        // Vérifier les valeurs par défaut
        $classeAttribut = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->first();
        
        $this->assertEquals(5, $classeAttribut->base_value);

        $personnageAttribut = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->first();
        
        $this->assertEquals(0, $personnageAttribut->value);
        
        // Nettoyer
        $attribut->delete();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);
    }

    public function test_attribute_update_triggers_recalculation(): void
    {
        // Créer un attribut de test
        $attribut = Attribut::create([
            'name' => 'Test Update',
            'slug' => 'test-update',
            'type' => 'int',
            'min_value' => 0,
            'max_value' => 20,
            'default_value' => 10
        ]);

        // Traiter les jobs de création
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que les relations ont été créées
        $classeAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        $personnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertGreaterThan(0, $classeAttributsCount);
        $this->assertGreaterThan(0, $personnageAttributsCount);

        // Modifier un champ impactant
        $attribut->update([
            'default_value' => 15,
            'max_value' => 25,
        ]);

        // Traiter les jobs de mise à jour
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que les relations existent toujours
        $updatedClasseAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertEquals($classeAttributsCount, $updatedClasseAttributsCount);
        
        // Nettoyer
        $attribut->delete();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);
    }

    public function test_attribute_update_without_impacting_changes_does_not_trigger_recalculation(): void
    {
        // Créer un attribut de test
        $attribut = Attribut::create([
            'name' => 'Test No Impact',
            'slug' => 'test-no-impact',
            'type' => 'int',
            'min_value' => 0,
            'max_value' => 20,
            'default_value' => 10
        ]);

        // Traiter les jobs de création
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Compter les relations avant modification
        $originalClasseAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        $originalPersonnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();

        // Modifier un champ non-impactant
        $attribut->update([
            'name' => 'Nouveau nom',
        ]);

        // Traiter les jobs (il ne devrait pas y en avoir)
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que les relations n'ont pas changé
        $newClasseAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        $newPersonnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertEquals($originalClasseAttributsCount, $newClasseAttributsCount);
        $this->assertEquals($originalPersonnageAttributsCount, $newPersonnageAttributsCount);
        
        // Nettoyer
        $attribut->delete();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);
    }

    public function test_attribute_deletion_cleans_up_relations(): void
    {
        // Créer un attribut de test
        $attribut = Attribut::create([
            'name' => 'Test Deletion',
            'slug' => 'test-deletion',
            'type' => 'int',
            'min_value' => 0,
            'max_value' => 100,
            'default_value' => 10
        ]);
        $attributId = $attribut->id;

        // Traiter les jobs de création
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que les relations ont été créées
        $classeAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attributId)
            ->count();
        $personnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attributId)
            ->count();

        $this->assertGreaterThan(0, $classeAttributsCount);
        $this->assertGreaterThan(0, $personnageAttributsCount);

        // Supprimer l'attribut
        $attribut->delete();

        // Traiter les jobs de suppression
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier que les relations ont été supprimées
        $remainingClasseAttributs = DB::table('classe_attributs')
            ->where('attribut_id', $attributId)
            ->count();
        $remainingPersonnageAttributs = DB::table('personnage_attributs')
            ->where('attribut_id', $attributId)
            ->count();

        $this->assertEquals(0, $remainingClasseAttributs);
        $this->assertEquals(0, $remainingPersonnageAttributs);
    }

    public function test_derived_attributes_do_not_create_character_relations(): void
    {
        // Créer un attribut dérivé
        $attribut = Attribut::create([
            'name' => 'PV Maximum Test',
            'slug' => 'pv-maximum-test',
            'type' => 'derived',
            'is_visible' => true,
        ]);

        // Traiter les jobs de création
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Vérifier qu'aucune relation personnage n'a été créée
        $personnageAttributsCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertEquals(0, $personnageAttributsCount);

        // Mais les relations classe doivent exister
        $classeAttributsCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        
        $this->assertGreaterThan(0, $classeAttributsCount);
        
        // Nettoyer
        $attribut->delete();
        $this->artisan('queue:work', ['--stop-when-empty' => true]);
    }

    public function test_cache_recalculation_job_processes_characters_in_batches(): void
    {
        // Créer plusieurs personnages pour tester le batching
        $user = User::factory()->create();
        $classe = Classe::first();
        
        for ($i = 0; $i < 5; $i++) {
            Personnage::factory()->create([
                'user_id' => $user->id,
                'classe_id' => $classe->id,
            ]);
        }

        $attribut = Attribut::first();
        
        // Exécuter le job de recalcul
        $job = new RecalculateCharacterStatsBatch($attribut->id, 'test', 2); // Batch size de 2
        $job->handle();

        // Vérifier que le cache a été mis à jour pour tous les personnages
        $cacheEntriesCount = DB::table('personnage_attributs_cache')
            ->where('attribut_id', $attribut->id)
            ->where('needs_recalculation', false)
            ->count();
        
        $totalPersonnages = Personnage::count();
        $this->assertEquals($totalPersonnages, $cacheEntriesCount);
    }

    public function test_idempotence_of_synchronization_operations(): void
    {
        $attribut = Attribut::first();
        
        // Compter les relations initiales
        $initialClasseCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        $initialPersonnageCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();

        // Exécuter la synchronisation plusieurs fois
        for ($i = 0; $i < 3; $i++) {
            $this->artisan('queue:work', ['--stop-when-empty' => true]);
        }

        // Vérifier qu'il n'y a pas de doublons
        $finalClasseCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();
        $finalPersonnageCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->count();

        $this->assertEquals($initialClasseCount, $finalClasseCount);
        $this->assertEquals($initialPersonnageCount, $finalPersonnageCount);
    }
}
