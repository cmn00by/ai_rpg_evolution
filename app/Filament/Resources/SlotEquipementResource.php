<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlotEquipementResource\Pages;
use App\Filament\Resources\SlotEquipementResource\RelationManagers;
use App\Models\SlotEquipement;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class SlotEquipementResource extends Resource
{
    protected static ?string $model = SlotEquipement::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 5;
    
    protected static ?string $navigationLabel = 'Slots d\'équipement';

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
                                    ->helperText('Identifiant unique pour le slot'),
                            ]),
                            
                        TextInput::make('max_per_slot')
                            ->label('Maximum par slot')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(1)
                            ->helperText('Nombre maximum d\'objets équipables dans ce slot (ex: 2 anneaux)'),
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
                    
                TextColumn::make('max_per_slot')
                    ->label('Max par slot')
                    ->sortable(),
                    
                TextColumn::make('objets_count')
                    ->label('Objets')
                    ->counts('objets')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les slots')
                    ->modalSubheading('Attention: cette action peut affecter les objets existants.'),
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
            'index' => Pages\ListSlotEquipements::route('/'),
            'create' => Pages\CreateSlotEquipement::route('/create'),
            'edit' => Pages\EditSlotEquipement::route('/{record}/edit'),
        ];
    }    
}
