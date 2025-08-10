<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventairePersonnageResource\Pages;
use App\Filament\Resources\InventairePersonnageResource\RelationManagers;
use App\Models\InventairePersonnage;
use App\Models\Personnage;
use App\Models\Objet;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventairePersonnageResource extends Resource
{
    protected static ?string $model = InventairePersonnage::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 6;
    
    protected static ?string $label = 'Inventaire';
    
    protected static ?string $pluralLabel = 'Inventaires';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('personnage_id')
                    ->label('Personnage')
                    ->relationship('personnage', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Sélectionnez le personnage propriétaire de cet objet'),
                    
                Select::make('objet_id')
                    ->label('Objet')
                    ->relationship('objet', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Sélectionnez l\'objet à ajouter à l\'inventaire'),
                    
                TextInput::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->helperText('Quantité de cet objet dans l\'inventaire'),
                    
                Toggle::make('is_equipped')
                    ->label('Équipé')
                    ->default(false)
                    ->helperText('Indique si cet objet est actuellement équipé'),
                    
                TextInput::make('durability_current')
                    ->label('Durabilité actuelle')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Durabilité actuelle de l\'objet (laissez vide pour utiliser la durabilité maximale)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('personnage.name')
                    ->label('Personnage')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('objet.name')
                    ->label('Objet')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('objet.rarete.name')
                    ->label('Rareté')
                    ->colors([
                        'secondary' => 'Commun',
                        'success' => 'Rare',
                        'warning' => 'Épique',
                        'danger' => 'Légendaire',
                    ])
                    ->sortable(),
                    
                TextColumn::make('objet.slot.name')
                    ->label('Slot')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('quantite')
                    ->label('Qté')
                    ->sortable(),
                    
                BooleanColumn::make('is_equipped')
                    ->label('Équipé')
                    ->sortable(),
                    
                TextColumn::make('durability_current')
                    ->label('Durabilité')
                    ->formatStateUsing(function ($state, $record) {
                        $max = $record->objet?->durability ?? 100;
                        $current = $state ?? $max;
                        return "{$current}/{$max}";
                    })
                    ->color(function ($state, $record) {
                        $max = $record->objet?->durability ?? 100;
                        $current = $state ?? $max;
                        $percentage = ($current / $max) * 100;
                        
                        if ($percentage <= 25) return 'danger';
                        if ($percentage <= 50) return 'warning';
                        return 'success';
                    })
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('personnage_id')
                    ->label('Personnage')
                    ->relationship('personnage', 'name')
                    ->searchable(),
                    
                SelectFilter::make('rarete_id')
                    ->label('Rareté')
                    ->options(\App\Models\RareteObjet::pluck('name', 'id'))
                    ->query(fn ($query, $data) => 
                        $query->when($data['value'], fn ($q) => 
                            $q->whereHas('objet', fn ($subQuery) => 
                                $subQuery->where('rarete_id', $data['value'])
                            )
                        )
                    )
                    ->searchable(),
                    
                SelectFilter::make('slot_id')
                    ->label('Slot d\'équipement')
                    ->options(\App\Models\SlotEquipement::pluck('name', 'id'))
                    ->query(fn ($query, $data) => 
                        $query->when($data['value'], fn ($q) => 
                            $q->whereHas('objet', fn ($subQuery) => 
                                $subQuery->where('slot_id', $data['value'])
                            )
                        )
                    )
                    ->searchable(),
                    
                SelectFilter::make('is_equipped')
                    ->label('État d\'équipement')
                    ->options([
                        '1' => 'Équipé',
                        '0' => 'Non équipé',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Action::make('equip')
                    ->label('Équiper')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_equipped && $record->objet?->slot)
                    ->requiresConfirmation()
                    ->modalHeading('Équiper cet objet')
                    ->modalSubheading('Cet objet sera équipé et remplacera l\'objet actuellement équipé dans ce slot.')
                    ->action(function ($record) {
                        // Déséquiper les autres objets du même slot
                        InventairePersonnage::where('personnage_id', $record->personnage_id)
                            ->whereHas('objet', function ($query) use ($record) {
                                $query->where('slot_id', $record->objet->slot_id);
                            })
                            ->update(['is_equipped' => false]);
                            
                        // Équiper cet objet
                        $record->update(['is_equipped' => true]);
                        
                        Notification::make()
                            ->title('Objet équipé')
                            ->body("{$record->objet->name} a été équipé sur {$record->personnage->name}.")
                            ->success()
                            ->send();
                    }),
                    
                Action::make('unequip')
                    ->label('Déséquiper')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_equipped)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_equipped' => false]);
                        
                        Notification::make()
                            ->title('Objet déséquipé')
                            ->body("{$record->objet->name} a été déséquipé de {$record->personnage->name}.")
                            ->success()
                            ->send();
                    }),
                    
                Action::make('repair')
                    ->label('Réparer')
                    ->icon('heroicon-o-wrench')
                    ->color('info')
                    ->visible(function ($record) {
                        $max = $record->objet?->durability ?? 100;
                        $current = $record->durability_current ?? $max;
                        return $current < $max;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Réparer cet objet')
                    ->modalSubheading('La durabilité sera restaurée au maximum.')
                    ->action(function ($record) {
                        $record->update(['durability_current' => $record->objet->durability]);
                        
                        Notification::make()
                            ->title('Objet réparé')
                            ->body("{$record->objet->name} a été réparé.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les objets sélectionnés')
                    ->modalSubheading('Êtes-vous sûr de vouloir supprimer ces objets de l\'inventaire ? Cette action est irréversible.')
                    ->modalButton('Supprimer'),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventairePersonnages::route('/'),
            'create' => Pages\CreateInventairePersonnage::route('/create'),
            'edit' => Pages\EditInventairePersonnage::route('/{record}/edit'),
        ];
    }    
}
