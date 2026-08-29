<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = '帳務與基礎設定';

    protected static ?string $modelLabel = '收支分類';

    protected static ?string $pluralModelLabel = '收支分類列表';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('分類設定')
                    ->schema([
                        Forms\Components\Select::make('family_id')
                            ->label('所屬家庭 (留空表系統預設)')
                            ->relationship('family', 'name')
                            ->nullable(),

                        Forms\Components\Select::make('parent_id')
                            ->label('上層父分類')
                            ->relationship('parent', 'name')
                            ->nullable(),

                        Forms\Components\TextInput::make('name')
                            ->label('分類名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('收支類型')
                            ->options([
                                'expense' => '支出',
                                'income' => '收入',
                                'both' => '收支皆可',
                            ])
                            ->required(),

                        Forms\Components\ColorPicker::make('color')
                            ->label('代表顏色')
                            ->default('#F59E0B'),

                        Forms\Components\TextInput::make('icon')
                            ->label('圖示')
                            ->placeholder('heroicon-o-cutlery'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('排序號')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_custom')
                            ->label('自訂分類')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'expense' => 'warning',
                        'income' => 'success',
                        default => 'info',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'expense' => '支出',
                        'income' => '收入',
                        'both' => '雙向',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('主分類')
                    ->placeholder('【主分類】'),
                Tables\Columns\TextColumn::make('name')
                    ->label('分類名稱')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')->label('顏色'),
                Tables\Columns\TextColumn::make('family.name')
                    ->label('適用家庭')
                    ->placeholder('全域系統預設'),
                Tables\Columns\IconColumn::make('is_custom')
                    ->label('成員自訂')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        'expense' => '支出',
                        'income' => '收入',
                    ]),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
