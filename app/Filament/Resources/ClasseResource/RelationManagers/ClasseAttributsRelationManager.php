<?php

namespace App\Filament\Resources\ClasseResource\RelationManagers;

use App\Models\Attribut;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClasseAttributsRelationManager extends RelationManager
{
    protected static string $relationship = 'attributs';

    protected static ?string $recordTitleAttribute = null;
    
    protected static ?string $title = 'Attributs de classe';
    
    protected static ?string $label = 'Attributs';
    
    protected static ?string $pluralLabel = 'Attributs';



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Attribut')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'base',
                        'warning' => 'derived',
                        'success' => 'computed_cached',
                    ]),
                    
                TextColumn::make('pivot.base_value')
                    ->label('Valeur de base')
                    ->sortable(),
                    
                TextColumn::make('min_value')
                    ->label('Min')
                    ->sortable(),
                    
                TextColumn::make('max_value')
                    ->label('Max')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type d\'attribut')
                    ->options([
                        'base' => 'Base',
                        'derived' => 'Dérivé',
                        'computed_cached' => 'Calculé',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Attacher un attribut')
                    ->form([
                        Select::make('recordId')
                            ->label('Attribut')
                            ->options(Attribut::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('base_value')
                            ->label('Valeur de base')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->helperText('Valeur de base de cet attribut pour cette classe'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        TextInput::make('base_value')
                            ->label('Valeur de base')
                            ->numeric()
                            ->required()
                            ->helperText('Valeur de base de cet attribut pour cette classe'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }    
}
