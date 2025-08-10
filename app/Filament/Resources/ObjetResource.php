<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObjetResource\Pages;
use App\Filament\Resources\ObjetResource\RelationManagers;
use App\Models\Objet;
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
use Illuminate\Support\Str;

class ObjetResource extends Resource
{
    protected static ?string $model = Objet::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 6;

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
                                    ->helperText('Identifiant unique pour l\'objet'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Select::make('rarete_id')
                                    ->label('Rareté')
                                    ->relationship('rarete', 'name')
                                    ->required()
                                    ->searchable(),
                                    
                                Select::make('slot_id')
                                    ->label('Slot d\'équipement')
                                    ->relationship('slot', 'name')
                                    ->nullable()
                                    ->searchable()
                                    ->helperText('Laisser vide pour les objets non équipables'),
                            ]),
                            
                        Grid::make(4)
                            ->schema([
                                Toggle::make('stackable')
                                    ->label('Empilable')
                                    ->default(false)
                                    ->helperText('Peut être empilé dans l\'inventaire'),
                                    
                                TextInput::make('buy_price')
                                    ->label('Prix d\'achat')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Prix pour acheter cet objet'),
                                    
                                TextInput::make('sell_price')
                                    ->label('Prix de vente')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Prix pour vendre cet objet'),
                                    
                                TextInput::make('durability')
                                    ->label('Durabilité')
                                    ->numeric()
                                    ->minValue(1)
                                    ->nullable()
                                    ->helperText('Durabilité maximale (laisser vide = indestructible)'),
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
                    
                BadgeColumn::make('rarete.name')
                    ->label('Rareté')
                    ->colors(fn ($record) => [
                        $record->rarete?->color_hex ?? '#6B7280' => $record->rarete?->name,
                    ]),
                    
                TextColumn::make('slot.name')
                    ->label('Slot')
                    ->placeholder('Non équipable')
                    ->sortable(),
                    
                BooleanColumn::make('stackable')
                    ->label('Empilable'),
                    
                TextColumn::make('buy_price')
                    ->label('Achat')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') . ' or' : '-'),
                    
                TextColumn::make('sell_price')
                    ->label('Vente')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') . ' or' : '-'),
                    
                TextColumn::make('durability')
                    ->label('Durabilité')
                    ->placeholder('∞')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rarete_id')
                    ->label('Rareté')
                    ->relationship('rarete', 'name'),
                    
                Tables\Filters\SelectFilter::make('slot_id')
                    ->label('Slot')
                    ->relationship('slot', 'name'),
                    
                Tables\Filters\TernaryFilter::make('stackable')
                    ->label('Empilable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('duplicate')
                    ->label('Dupliquer')
                    ->icon('heroicon-o-duplicate')
                    ->color('secondary')
                    ->action(function (Objet $record) {
                        $newObjet = $record->replicate();
                        $newObjet->name = $record->name . ' (Copie)';
                        $newObjet->slug = $record->slug . '-copie-' . time();
                        $newObjet->save();
                        
                        // Dupliquer les attributs
                        foreach ($record->objetAttributs as $objetAttribut) {
                            $newObjet->objetAttributs()->create([
                                'attribut_id' => $objetAttribut->attribut_id,
                                'flat_value' => $objetAttribut->flat_value,
                                'percent_value' => $objetAttribut->percent_value,
                            ]);
                        }
                        
                        Notification::make()
                            ->title('Objet dupliqué')
                            ->body("L'objet {$record->name} a été dupliqué avec succès.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Dupliquer l\'objet')
                    ->modalSubheading('Cette action créera une copie de cet objet avec tous ses attributs.'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les objets')
                    ->modalSubheading('Attention: cette action peut affecter les boutiques et inventaires.'),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\ObjetAttributsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObjets::route('/'),
            'create' => Pages\CreateObjet::route('/create'),
            'edit' => Pages\EditObjet::route('/{record}/edit'),
        ];
    }    
}
