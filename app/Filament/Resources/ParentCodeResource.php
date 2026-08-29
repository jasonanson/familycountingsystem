<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentCodeResource\Pages;
use App\Models\ParentCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParentCodeResource extends Resource
{
    protected static ?string $model = ParentCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = '系統與家庭管理';

    protected static ?string $modelLabel = '家長註冊碼';

    protected static ?string $pluralModelLabel = '家長註冊碼列表';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('家長註冊碼資訊')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('註冊碼')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('啟用狀態')
                            ->default(true),

                        Forms\Components\Select::make('created_by_user_id')
                            ->label('建立者')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->nullable()
                            ->default(fn () => auth()->id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('註冊碼')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('啟用狀態')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('建立者')
                    ->placeholder('系統建立'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('啟用狀態')
                    ->trueLabel('僅顯示啟用')
                    ->falseLabel('僅顯示停用'),
            ])
            ->actions([
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
            'index' => Pages\ListParentCodes::route('/'),
            'create' => Pages\CreateParentCode::route('/create'),
            'edit' => Pages\EditParentCode::route('/{record}/edit'),
        ];
    }
}
