<?php

namespace App\Filament\Resources\ObjetResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ObjetAttributsRelationManager extends RelationManager
{
    protected static string $relationship = 'objetAttributs';

    protected static ?string $recordTitleAttribute = 'attribut.name';
    
    protected static ?string $title = 'Attributs & Modifiers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('attribut_id')
                            ->label('Attribut')
                            ->relationship('attribut', 'name')
                            ->required()
                            ->searchable()
                            ->helperText('Attribut à modifier'),
                            
                        TextInput::make('flat_value')
                            ->label('Valeur fixe')
                            ->numeric()
                            ->default(0)
                            ->helperText('Bonus/malus en valeur absolue'),
                            
                        TextInput::make('percent_value')
                            ->label('Valeur en %')
                            ->numeric()
                            ->minValue(-100)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%')
                            ->helperText('Bonus/malus en pourcentage (max ±100%)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attribut.name')
                    ->label('Attribut')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('attribut.type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'base',
                        'success' => 'derived',
                        'warning' => 'computed_cached',
                    ]),
                    
                TextColumn::make('flat_value')
                    ->label('Valeur fixe')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+' . $state : $state),
                    
                TextColumn::make('percent_value')
                    ->label('Pourcentage')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+' . $state . '%' : $state . '%'),
                    
                TextColumn::make('attribut.min_value')
                    ->label('Min')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('attribut.max_value')
                    ->label('Max')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attribut.type')
                    ->label('Type d\'attribut')
                    ->options([
                        'base' => 'Base',
                        'derived' => 'Dérivé',
                        'computed_cached' => 'Calculé en cache',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un attribut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les attributs')
                    ->modalSubheading('Cette action supprimera définitivement ces modifiers.'),
            ])
            ->defaultSort('attribut.order');
    }    
}
