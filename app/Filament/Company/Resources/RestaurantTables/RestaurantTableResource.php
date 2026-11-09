<?php

namespace App\Filament\Company\Resources\RestaurantTables;

use App\Filament\Company\Resources\RestaurantTables\Pages;
use App\Models\Branch;
use App\Models\RestaurantArea;
use App\Models\RestaurantTable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Management';

    protected static ?string $navigationLabel = 'Restaurant Tables';

    protected static ?string $modelLabel = 'Restaurant Table';

    protected static ?string $pluralModelLabel = 'Restaurant Tables';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('branch_id')
                ->label('Branch')
                ->options(function () {
                    $user = Auth::user();

                    return Branch::query()
                        ->where('company_id', $user->company_id)
                        ->when(
                            $user->branch_id,
                            fn ($query) => $query->where('id', $user->branch_id)
                        )
                        ->orderBy('branch_name')
                        ->pluck('branch_name', 'id');
                })
                ->default(fn () => Auth::user()->branch_id)
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn () => filled(Auth::user()->branch_id))
                ->dehydrated(),

            Select::make('area_id')
                ->label('Dining Area')
                ->options(function (Get $get) {
                    $user = Auth::user();
                    $branchId = $get('branch_id');

                    if (!$branchId) {
                        return [];
                    }

                    return RestaurantArea::query()
                        ->where('company_id', $user->company_id)
                        ->where('branch_id', $branchId)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (Get $get) => blank($get('branch_id')))
                ->helperText('Select the dining area where this table is located.'),

            TextInput::make('table_number')
                ->label('Table Number / Name')
                ->placeholder('e.g. Table 01')
                ->required()
                ->maxLength(30),

            TextInput::make('capacity')
                ->label('Seating Capacity')
                ->numeric()
                ->required()
                ->default(2)
                ->minValue(1)
                ->maxValue(100)
                ->suffix('seats'),

            Select::make('status')
                ->label('Table Status')
                ->options([
                    RestaurantTable::STATUS_AVAILABLE => 'Available',
                    RestaurantTable::STATUS_RESERVED => 'Reserved',
                    RestaurantTable::STATUS_CLEANING => 'Cleaning',
                ])
                ->default(RestaurantTable::STATUS_AVAILABLE)
                ->required()
                ->helperText('Occupied status is managed by the ordering workflow.'),

            Textarea::make('notes')
                ->label('Notes')
                ->placeholder('Optional notes about this table')
                ->rows(3)
                ->maxLength(255)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive tables cannot be assigned to new orders.'),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('table_number')
                    ->label('Table')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Dining Area')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->suffix(' seats')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'reserved' => 'Reserved',
                        'cleaning' => 'Cleaning',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'reserved' => 'warning',
                        'cleaning' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('table_number')
            ->filters([

                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Dining Area')
                    ->options(fn () => RestaurantArea::query()
                        ->where('company_id', Auth::user()->company_id)
                        ->when(
                            Auth::user()->branch_id,
                            fn ($query) => $query->where(
                                'branch_id',
                                Auth::user()->branch_id
                            )
                        )
                        ->pluck('name', 'id')
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'reserved' => 'Reserved',
                        'cleaning' => 'Cleaning',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No restaurant tables')
            ->emptyStateDescription(
                'Create your first restaurant table and assign it to a dining area.'
            )
            ->emptyStateIcon('heroicon-o-squares-2x2');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->where('company_id', $user->company_id)
            ->when(
                $user->branch_id,
                fn (Builder $query) => $query->where(
                    'branch_id',
                    $user->branch_id
                )
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantTables::route('/'),
            'create' => Pages\CreateRestaurantTable::route('/create'),
            'edit' => Pages\EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}