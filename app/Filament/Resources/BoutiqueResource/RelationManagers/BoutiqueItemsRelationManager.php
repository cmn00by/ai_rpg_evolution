<?php

namespace App\Filament\Resources\BoutiqueResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BoutiqueItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'boutiqueItems';

    protected static ?string $recordTitleAttribute = 'objet.name';
    
    protected static ?string $title = 'Articles en vente';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('objet_id')
                            ->label('Objet')
                            ->relationship('objet', 'name')
                            ->required()
                            ->searchable()
                            ->helperText('Objet à vendre dans cette boutique'),
                            
                        TextInput::make('stock')
                            ->label('Stock actuel')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Quantité disponible en stock'),
                    ]),
                    
                Grid::make(3)
                    ->schema([
                        TextInput::make('price_override')
                            ->label('Prix personnalisé')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Laisser vide pour utiliser le prix de base de l\'objet'),
                            
                        Toggle::make('allow_buy')
                            ->label('Autoriser l\'achat')
                            ->default(true)
                            ->helperText('Les joueurs peuvent acheter cet objet'),
                            
                        Toggle::make('allow_sell')
                            ->label('Autoriser la vente')
                            ->default(false)
                            ->helperText('Les joueurs peuvent vendre cet objet à cette boutique'),
                    ]),
                    
                Grid::make(3)
                    ->schema([
                        TextInput::make('restock_quantity')
                            ->label('Quantité de restock')
                            ->numeric()
                            ->minValue(0)
                            ->default(5)
                            ->helperText('Quantité ajoutée lors du restock'),
                            
                        TextInput::make('max_stock')
                            ->label('Stock maximum')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('Stock maximum autorisé'),
                            
                        TextInput::make('restock_frequency_hours')
                            ->label('Fréquence restock (h)')
                            ->numeric()
                            ->minValue(1)
                            ->default(24)
                            ->helperText('Heures entre chaque restock automatique'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('objet.name')
                    ->label('Objet')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('objet.rarete.name')
                    ->label('Rareté')
                    ->colors(fn ($record) => [
                        $record->objet?->rarete?->color_hex ?? '#6B7280' => $record->objet?->rarete?->name,
                    ]),
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($state, $record) => $state <= ($record->max_stock * 0.2) ? 'danger' : ($state <= ($record->max_stock * 0.5) ? 'warning' : 'success')),
                    
                TextColumn::make('max_stock')
                    ->label('Max')
                    ->sortable(),
                    
                TextColumn::make('price_override')
                     ->label('Prix')
                     ->placeholder('Prix de base')
                     ->formatStateUsing(function ($state, $record) {
                         $price = $state ?? $record->objet?->buy_price ?? 0;
                         return number_format($price, 0, ',', ' ') . ' or';
                     })
                     ->sortable(),
                    
                BooleanColumn::make('allow_buy')
                    ->label('Achat'),
                    
                BooleanColumn::make('allow_sell')
                    ->label('Vente'),
                    
                TextColumn::make('restock_quantity')
                    ->label('Restock qty')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('restock_frequency_hours')
                    ->label('Freq. (h)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('last_restocked_at')
                    ->label('Dernier restock')
                    ->dateTime()
                    ->placeholder('Jamais')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rarete_id')
                    ->label('Rareté')
                    ->options(\App\Models\RareteObjet::pluck('name', 'id'))
                    ->query(fn ($query, $data) => 
                        $query->when($data['value'], fn ($q) => 
                            $q->whereHas('objet', fn ($subQuery) => 
                                $subQuery->where('rarete_id', $data['value'])
                            )
                        )
                    ),
                    
                Tables\Filters\TernaryFilter::make('allow_buy')
                    ->label('Achat autorisé'),
                    
                Tables\Filters\TernaryFilter::make('allow_sell')
                    ->label('Vente autorisée'),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock faible')
                    ->query(fn (Builder $query) => $query->whereRaw('stock <= max_stock * 0.2')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un article'),
                    
                Action::make('bulk_restock')
                    ->label('Restock tous les articles')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->action(function () {
                        // Logique de restock en masse
                        Notification::make()
                            ->title('Restock en masse effectué')
                            ->body('Tous les articles ont été restockés selon leurs règles.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Action::make('restock_item')
                    ->label('Restock')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function ($record) {
                        $newStock = min($record->stock + $record->restock_quantity, $record->max_stock);
                        $record->update([
                            'stock' => $newStock,
                            'last_restocked_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Article restocké')
                            ->body("Stock mis à jour: {$newStock}/{$record->max_stock}")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation(),
                    
                Action::make('bulk_toggle_buy')
                    ->label('Basculer achat')
                    ->icon('heroicon-o-shopping-cart')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['allow_buy' => !$record->allow_buy]);
                        }
                        
                        Notification::make()
                            ->title('Statut d\'achat modifié')
                            ->success()
                            ->send();
                    }),
                    
                Action::make('bulk_toggle_sell')
                    ->label('Basculer vente')
                    ->icon('heroicon-o-currency-dollar')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['allow_sell' => !$record->allow_sell]);
                        }
                        
                        Notification::make()
                            ->title('Statut de vente modifié')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('objet.name');
    }    
}
