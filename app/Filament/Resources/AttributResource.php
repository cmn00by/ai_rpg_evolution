<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributResource\Pages;
use App\Filament\Resources\AttributResource\RelationManagers;
use App\Models\Attribut;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class AttributResource extends Resource
{
    protected static ?string $model = Attribut::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 1;

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
                                    
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Identifiant unique pour l\'attribut'),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->required()
                                    ->options([
                                        'int' => 'Entier',
                                        'float' => 'Décimal',
                                        'bool' => 'Booléen',
                                        'derived' => 'Dérivé',
                                        'computed_cached' => 'Calculé (mis en cache)',
                                    ])
                                    ->default('int')
                                    ->helperText('Les types dérivés/calculés ne peuvent pas être modifiés manuellement'),
                                    
                                TextInput::make('min_value')
                                    ->label('Valeur minimale')
                                    ->numeric()
                                    ->default(0),
                                    
                                TextInput::make('max_value')
                                    ->label('Valeur maximale')
                                    ->numeric()
                                    ->default(999999),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('default_value')
                                    ->label('Valeur par défaut')
                                    ->numeric()
                                    ->default(0),
                                    
                                TextInput::make('order')
                                    ->label('Ordre d\'affichage')
                                    ->numeric()
                                    ->default(0),
                                    
                                Toggle::make('is_visible')
                                    ->label('Visible')
                                    ->default(true)
                                    ->helperText('Afficher cet attribut dans l\'interface joueur'),
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
                    
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'base',
                        'warning' => 'derived',
                        'success' => 'computed_cached',
                    ]),
                    
                TextColumn::make('min_value')
                    ->label('Min')
                    ->sortable(),
                    
                TextColumn::make('max_value')
                    ->label('Max')
                    ->sortable(),
                    
                TextColumn::make('default_value')
                    ->label('Défaut')
                    ->sortable(),
                    
                BooleanColumn::make('is_visible')
                    ->label('Visible'),
                    
                TextColumn::make('order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'base' => 'Base',
                        'derived' => 'Dérivé',
                        'computed_cached' => 'Calculé',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('recalculate')
                    ->label('Recalculer')
                    ->icon('heroicon-o-refresh')
                    ->color('warning')
                    ->action(function (Attribut $record) {
                        // TODO: Implémenter le job de recalcul
                        Notification::make()
                            ->title('Recalcul lancé')
                            ->body("Le recalcul de l'attribut {$record->name} a été mis en file d'attente.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Recalculer les caches')
                    ->modalSubheading('Cette action va recalculer tous les caches liés à cet attribut.'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les attributs')
                    ->modalSubheading('Attention: cette action peut affecter les classes, personnages et objets.'),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListAttributs::route('/'),
            'create' => Pages\CreateAttribut::route('/create'),
            'edit' => Pages\EditAttribut::route('/{record}/edit'),
        ];
    }    
}
