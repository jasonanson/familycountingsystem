<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = '帳務與基礎設定';

    protected static ?string $modelLabel = '帳戶';

    protected static ?string $pluralModelLabel = '帳戶列表';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('帳戶設定')
                    ->schema([
                        Forms\Components\Select::make('family_id')
                            ->label('所屬家庭')
                            ->relationship('family', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('帳戶名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('帳戶類型')
                            ->options([
                                'cash' => '現金',
                                'bank' => '銀行存款',
                                'credit' => '信用卡',
                                'ewallet' => '電子支付',
                                'custom' => '其他/自訂',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('type_custom')
                            ->label('自訂類型名稱')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'custom')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('balance')
                            ->label('目前餘額 (TWD)')
                            ->numeric()
                            ->prefix('NT$')
                            ->default(0.00)
                            ->required(),

                        Forms\Components\ColorPicker::make('color')
                            ->label('代表顏色')
                            ->default('#3B82F6'),

                        Forms\Components\TextInput::make('icon')
                            ->label('圖示類別')
                            ->placeholder('heroicon-o-banknotes'),

                        Forms\Components\Toggle::make('is_archived')
                            ->label('已停用/封存')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('family.name')->label('家庭')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('帳戶名稱')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('類型')
                    ->formatStateUsing(fn ($state, $record) => $state === 'custom' ? ($record->type_custom ?: '自訂') : match ($state) {
                        'cash' => '現金',
                        'bank' => '銀行',
                        'credit' => '信用卡',
                        'ewallet' => '電支',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('balance')->label('餘額')->money('TWD')->sortable(),
                Tables\Columns\ColorColumn::make('color')->label('顏色'),
                Tables\Columns\IconColumn::make('is_archived')->label('狀態')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('family_id')
                    ->label('篩選家庭')
                    ->relationship('family', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
