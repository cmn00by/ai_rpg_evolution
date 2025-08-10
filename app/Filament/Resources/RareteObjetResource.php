<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RareteObjetResource\Pages;
use App\Filament\Resources\RareteObjetResource\RelationManagers;
use App\Models\RareteObjet;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class RareteObjetResource extends Resource
{
    protected static ?string $model = RareteObjet::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Raretés';

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
                                    ->helperText('Identifiant unique pour la rareté'),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('multiplier')
                                    ->label('Multiplicateur')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->maxValue(10)
                                    ->default(1.0)
                                    ->helperText('Multiplicateur appliqué aux stats des objets'),
                                    
                                ColorPicker::make('color_hex')
                                    ->label('Couleur')
                                    ->default('#6B7280')
                                    ->helperText('Couleur d\'affichage de la rareté'),
                                    
                                TextInput::make('order')
                                    ->label('Ordre')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Ordre d\'affichage (0 = plus rare)'),
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
                    
                TextColumn::make('multiplier')
                    ->label('Multiplicateur')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'x' . number_format($state, 1)),
                    
                ColorColumn::make('color_hex')
                    ->label('Couleur'),
                    
                TextColumn::make('order')
                    ->label('Ordre')
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
                    ->modalHeading('Supprimer les raretés')
                    ->modalSubheading('Attention: cette action peut affecter les objets existants.'),
            ])
            ->defaultSort('order');
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
            'index' => Pages\ListRareteObjets::route('/'),
            'create' => Pages\CreateRareteObjet::route('/create'),
            'edit' => Pages\EditRareteObjet::route('/{record}/edit'),
        ];
    }    
}
