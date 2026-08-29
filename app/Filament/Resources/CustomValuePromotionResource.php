<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomValuePromotionResource\Pages;
use App\Models\Category;
use App\Models\CustomValuePromotion;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomValuePromotionResource extends Resource
{
    protected static ?string $model = CustomValuePromotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationGroup = '例外與自訂審核';

    protected static ?string $modelLabel = '自訂值升格申請';

    protected static ?string $pluralModelLabel = '自訂值升格審核';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('升格申請細節')
                    ->schema([
                        Forms\Components\Select::make('family_id')
                            ->label('家庭')
                            ->relationship('family', 'name')
                            ->required(),

                        Forms\Components\Select::make('proposed_by_user_id')
                            ->label('提議成員')
                            ->relationship('proposedBy', 'name')
                            ->required(),

                        Forms\Components\Select::make('field_type')
                            ->label('欄位類型')
                            ->options([
                                'category' => '分類 (Category)',
                                'tag' => '標籤 (Tag)',
                                'account' => '帳戶 (Account)',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('proposed_value')
                            ->label('自訂填寫值')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('審核狀態')
                            ->options([
                                'pending' => '待審核',
                                'approved' => '已核准升格',
                                'rejected' => '已駁回',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('family.name')->label('家庭'),
                Tables\Columns\TextColumn::make('proposedBy.name')->label('提議者'),
                Tables\Columns\TextColumn::make('field_type')
                    ->label('欄位')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'category' => '分類',
                        'tag' => '標籤',
                        'account' => '帳戶',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('proposed_value')
                    ->label('自訂輸入內容')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => '待審核',
                        'approved' => '已升格共用',
                        'rejected' => '已駁回',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('申請時間')->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('核准升格')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CustomValuePromotion $record) => $record->status === 'pending')
                    ->action(function (CustomValuePromotion $record) {
                        if ($record->field_type === 'category') {
                            Category::create([
                                'family_id' => $record->family_id,
                                'name' => $record->proposed_value,
                                'type' => 'expense',
                                'is_custom' => false,
                                'scope' => 'family',
                            ]);
                        }
                        $record->update(['status' => 'approved']);
                        Notification::make()->title('已升格為家庭共用選項')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('駁回')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (CustomValuePromotion $record) => $record->status === 'pending')
                    ->action(function (CustomValuePromotion $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('已駁回升格申請')->danger()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomValuePromotions::route('/'),
        ];
    }
}
