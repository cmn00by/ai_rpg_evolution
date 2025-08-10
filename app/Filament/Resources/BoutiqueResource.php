<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoutiqueResource\Pages;
use App\Filament\Resources\BoutiqueResource\RelationManagers;
use App\Models\Boutique;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BoutiqueResource extends Resource
{
    protected static ?string $model = Boutique::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                                    
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Identifiant unique pour la boutique'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Boutique active')
                                    ->default(true)
                                    ->helperText('Désactiver pour fermer temporairement la boutique'),
                                    
                                TextInput::make('tax_rate')
                                    ->label('Taux de taxe (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%')
                                    ->helperText('Taxe appliquée sur les achats'),
                            ]),
                            
                        KeyValue::make('config_json')
                            ->label('Configuration JSON')
                            ->keyLabel('Paramètre')
                            ->valueLabel('Valeur')
                            ->helperText('Configuration avancée : slots autorisés, raretés, whitelist/blacklist objets, remises')
                            ->default([
                                'allowed_slots' => [],
                                'allowed_rarities' => [],
                                'whitelist_objects' => [],
                                'blacklist_objects' => [],
                                'discount_rate' => 0,
                                'restock_frequency' => 24,
                                'max_stock_per_item' => 10,
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                    
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                    
                TextColumn::make('tax_rate')
                    ->label('Taxe')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state . '%'),
                    
                TextColumn::make('boutique_items_count')
                    ->label('Articles')
                    ->counts('boutiqueItems')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Boutique active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('restock')
                    ->label('Restock maintenant')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->action(function (Boutique $record) {
                        // Logique de restock à implémenter
                        // Pour l'instant, on simule juste une notification
                        Notification::make()
                            ->title('Restock effectué')
                            ->body("Le restock de la boutique {$record->name} a été lancé.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Effectuer un restock')
                    ->modalSubheading('Cette action va renouveler le stock selon les règles configurées.'),
                    
                Action::make('toggle_active')
                    ->label(fn (Boutique $record) => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (Boutique $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Boutique $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (Boutique $record) {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        Notification::make()
                            ->title('Boutique ' . ($record->is_active ? 'activée' : 'désactivée'))
                            ->body("La boutique {$record->name} a été " . ($record->is_active ? 'activée' : 'désactivée') . ".")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les boutiques')
                    ->modalSubheading('Attention: cette action peut affecter l\'historique des achats.'),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\BoutiqueItemsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoutiques::route('/'),
            'create' => Pages\CreateBoutique::route('/create'),
            'edit' => Pages\EditBoutique::route('/{record}/edit'),
        ];
    }    
}
