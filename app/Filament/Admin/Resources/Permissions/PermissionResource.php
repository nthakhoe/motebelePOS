<?php

namespace App\Filament\Admin\Resources\Permissions;

use App\Filament\Admin\Resources\Permissions\Pages\CreatePermission;
use App\Filament\Admin\Resources\Permissions\Pages\EditPermission;
use App\Filament\Admin\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Admin\Resources\Permissions\Schemas\PermissionForm;
use App\Filament\Admin\Resources\Permissions\Tables\PermissionsTable;
use App\Models\Permission;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Table;
use Filament\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make('Permission Details')
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([

                        Select::make('module')
                            ->required()
                            ->live()
                            ->options([
                                'Company'      => 'Company',
                                'Branch'       => 'Branch',
                                'Terminal'     => 'Terminal',
                                'User'         => 'Users',
                                'Role'         => 'Roles',
                                'Permission'   => 'Permissions',
                                'Customer'     => 'Customers',
                                'Supplier'     => 'Suppliers',
                                'Product'      => 'Products',
                                'Category'     => 'Categories',
                                'Inventory'    => 'Inventory',
                                'Purchase'     => 'Purchases',
                                'Sale'         => 'Sales',
                                'Payment'      => 'Payments',
                                'Receipt'      => 'Receipts',
                                'Register'     => 'Registers',
                                'Report'       => 'Reports',
                                'Dashboard'    => 'Dashboard',
                                'Settings'     => 'Settings',
                                'Lekuka'       => 'Lekuka E-Invoicing',
                            ]),

                        Select::make('action')
                            ->required()
                            ->live()
                            ->options([
                                'view' => 'View',
                                'create' => 'Create',
                                'update' => 'Update',
                                'delete' => 'Delete',
                                'approve' => 'Approve',
                                'cancel' => 'Cancel',
                                'print' => 'Print',
                                'export' => 'Export',
                                'manage' => 'Manage',
                            ]),

                        TextInput::make('name')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->name);
                                }
                            })
                            ->afterStateUpdated(function ($set, $get) {
                                if ($get('module') && $get('action')) {
                                    $set(
                                        'name',
                                        strtolower($get('module'))
                                        . '.'
                                        . strtolower($get('action'))
                                    );
                                }
                            }),

                        Hidden::make('guard_name')
                            ->default('web'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('module')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                /*Tables\Columns\TextColumn::make('roles_count')
                    ->counts('roles')
                    ->label('Roles')
                    ->badge()
                    ->color('warning'),*/

                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),

            ]);
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
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Super Admin');
    }
}
