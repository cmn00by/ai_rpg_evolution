<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AchatHistoriqueResource\Pages;
use App\Filament\Resources\AchatHistoriqueResource\RelationManagers;
use App\Models\AchatHistorique;
use App\Models\Boutique;
use App\Models\Personnage;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class AchatHistoriqueResource extends Resource
{
    protected static ?string $model = AchatHistorique::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'RPG';
    
    protected static ?int $navigationSort = 7;
    
    protected static ?string $label = 'Historique des achats';
    
    protected static ?string $pluralLabel = 'Historique des achats';

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
                    ->disabled(),
                    
                Select::make('boutique_id')
                    ->label('Boutique')
                    ->relationship('boutique', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(),
                    
                Select::make('objet_id')
                    ->label('Objet')
                    ->relationship('objet', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(),
                    
                TextInput::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->disabled(),
                    
                TextInput::make('prix_unitaire')
                    ->label('Prix unitaire')
                    ->numeric()
                    ->suffix('or')
                    ->required()
                    ->disabled(),
                    
                TextInput::make('prix_total')
                    ->label('Prix total')
                    ->numeric()
                    ->suffix('or')
                    ->required()
                    ->disabled(),
                    
                Select::make('type_transaction')
                    ->label('Type de transaction')
                    ->options([
                        'achat' => 'Achat',
                        'vente' => 'Vente',
                    ])
                    ->required()
                    ->disabled(),
                    
                DateTimePicker::make('created_at')
                    ->label('Date de transaction')
                    ->disabled(),
                    
                Textarea::make('meta_json')
                    ->label('Métadonnées JSON')
                    ->rows(10)
                    ->disabled()
                    ->helperText('Informations détaillées sur la transaction (solde avant/après, taxes, remises, etc.)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('personnage.name')
                    ->label('Personnage')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('boutique.name')
                    ->label('Boutique')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('objet.name')
                    ->label('Objet')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('type_transaction')
                    ->label('Type')
                    ->colors([
                        'success' => 'achat',
                        'warning' => 'vente',
                    ])
                    ->sortable(),
                    
                TextColumn::make('quantite')
                    ->label('Qté')
                    ->sortable(),
                    
                TextColumn::make('prix_unitaire')
                    ->label('Prix unitaire')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' or')
                    ->sortable(),
                    
                TextColumn::make('prix_total')
                    ->label('Prix total')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' or')
                    ->sortable(),
                    
                TextColumn::make('personnage.gold')
                    ->label('Or restant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' or')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type_transaction')
                    ->label('Type de transaction')
                    ->options([
                        'achat' => 'Achat',
                        'vente' => 'Vente',
                    ]),
                    
                SelectFilter::make('boutique_id')
                    ->label('Boutique')
                    ->relationship('boutique', 'name')
                    ->searchable(),
                    
                SelectFilter::make('personnage_id')
                    ->label('Personnage')
                    ->relationship('personnage', 'name')
                    ->searchable(),
                    
                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label('Date de début'),
                        DateTimePicker::make('created_until')
                            ->label('Date de fin'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir détails'),
            ])
            ->bulkActions([
                // Pas de suppression en masse pour l'historique
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
            'index' => Pages\ListAchatHistoriques::route('/'),
            'view' => Pages\ViewAchatHistorique::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }    
}
