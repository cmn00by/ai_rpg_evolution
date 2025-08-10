<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonnageResource\Pages;
use App\Filament\Resources\PersonnageResource\RelationManagers;
use App\Models\Personnage;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonnageResource extends Resource
{
    protected static ?string $model = Personnage::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 3;

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
                                    ->maxLength(255),
                                    
                                Select::make('classe_id')
                                    ->label('Classe')
                                    ->relationship('classe', 'name')
                                    ->required()
                                    ->searchable(),
                            ]),
                            
                        Grid::make(4)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Joueur')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->searchable(),
                                    
                                TextInput::make('level')
                                    ->label('Niveau')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(100),
                                    
                                TextInput::make('gold')
                                    ->label('Or')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                    
                                TextInput::make('reputation')
                                    ->label('Réputation')
                                    ->numeric()
                                    ->default(0),
                            ]),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Personnage actif')
                            ->default(false),
                    ])
                    ->columnSpan('full'),
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
                    
                TextColumn::make('classe.name')
                    ->label('Classe')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('level')
                    ->label('Niveau')
                    ->sortable(),
                    
                TextColumn::make('gold')
                    ->label('Or')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' or'),
                    
                TextColumn::make('user.name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable(),
                    
                BooleanColumn::make('is_active')
                    ->label('Actif')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classe')
                    ->relationship('classe', 'name'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Personnage actif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('set_active')
                    ->label('Définir actif')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (Personnage $record) {
                        // Désactiver tous les autres personnages du même utilisateur
                        Personnage::where('user_id', $record->user_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_active' => false]);
                            
                        // Activer ce personnage
                        $record->update(['is_active' => true]);
                        
                        Notification::make()
                            ->title('Personnage activé')
                            ->body("Le personnage {$record->name} est maintenant actif.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Définir comme personnage actif')
                    ->modalSubheading('Ce personnage deviendra le personnage actif du joueur.'),
                    
                Action::make('recalculate')
                    ->label('Recalculer')
                    ->icon('heroicon-o-refresh')
                    ->color('secondary')
                    ->action(function (Personnage $record) {
                        // TODO: Implémenter le recalcul des stats
                        Notification::make()
                            ->title('Recalcul lancé')
                            ->body("Le recalcul des stats de {$record->name} a été mis en file d'attente.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les personnages')
                    ->modalSubheading('Attention: cette action supprimera définitivement les personnages.'),
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
            'index' => Pages\ListPersonnages::route('/'),
            'create' => Pages\CreatePersonnage::route('/create'),
            'edit' => Pages\EditPersonnage::route('/{record}/edit'),
        ];
    }    
}
