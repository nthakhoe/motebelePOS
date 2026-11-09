<?php

namespace App\Filament\Company\Resources\RestaurantAreas;

use App\Filament\Company\Resources\RestaurantAreas\Pages;
use App\Models\Branch;
use App\Models\RestaurantArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;

class RestaurantAreaResource extends Resource
{
    protected static ?string $model = RestaurantArea::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Management';

    protected static ?string $navigationLabel = 'Dining Areas';

    protected static ?string $modelLabel = 'Dining Area';

    protected static ?string $pluralModelLabel = 'Dining Areas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

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
                    ->disabled(fn () => filled(Auth::user()->branch_id))
                    ->dehydrated(),

                TextInput::make('name')
                    ->label('Dining Area Name')
                    ->placeholder('e.g. Indoor Dining')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true),

                TextInput::make('code')
                    ->label('Area Code')
                    ->placeholder('e.g. INDOOR')
                    ->required()
                    ->maxLength(30)
                    ->alphaDash()
                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                    ->rules(function (?RestaurantArea $record) {
                        return [
                            Rule::unique('restaurant_areas', 'code')
                                ->where('company_id', Auth::user()->company_id)
                                ->where('branch_id', Auth::user()->branch_id ?? request()->input('data.branch_id'))
                                ->ignore($record?->id),
                        ];
                    }),

                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Brief description of this dining area')
                    ->rows(3)
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive areas will not be available when managing restaurant tables.'),

            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Dining Area')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tables_count')
                    ->label('Tables')
                    ->counts('tables')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Display Order')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All areas')
                    ->trueLabel('Active areas')
                    ->falseLabel('Inactive areas'),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (RestaurantArea $record) =>
                        !$record->tables()->exists()
                    )
                    ->requiresConfirmation(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No dining areas created')
            ->emptyStateDescription('Create your first dining area to start organising restaurant tables.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->where('company_id', $user->company_id)
            ->when(
                $user->branch_id,
                fn (Builder $query) => $query->where('branch_id', $user->branch_id)
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantAreas::route('/'),
            'create' => Pages\CreateRestaurantArea::route('/create'),
            'edit' => Pages\EditRestaurantArea::route('/{record}/edit'),
        ];
    }
}