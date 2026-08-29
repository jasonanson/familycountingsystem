<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FamilyResource\Pages;
use App\Models\Family;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = '系統與家庭管理';

    protected static ?string $modelLabel = '家庭';

    protected static ?string $pluralModelLabel = '家庭列表';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本資料')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('家庭名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('owner_user_id')
                            ->label('家庭擁有者 (戶長)')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('created_by_user_id')
                            ->label('建立者')
                            ->relationship('createdBy', 'name')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('total_pool_amount')
                            ->label('總預算池金額 (TWD)')
                            ->numeric()
                            ->prefix('NT$')
                            ->default(0.00)
                            ->required(),

                        Forms\Components\TextInput::make('storage_quota_mb')
                            ->label('附件容量上限 (MB)')
                            ->numeric()
                            ->default(500)
                            ->required(),

                        Forms\Components\TextInput::make('discord_webhook_url')
                            ->label('Discord Webhook 通知 URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://discord.com/api/webhooks/...'),

                        Forms\Components\Toggle::make('is_archived')
                            ->label('已歸檔/停用')
                            ->default(false),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('家庭名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('戶長')
                    ->placeholder('未設定'),

                Tables\Columns\TextColumn::make('total_pool_amount')
                    ->label('總預算池')
                    ->money('TWD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('成員人數')
                    ->counts('members'),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label('狀態')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label('歸檔狀態')
                    ->trueLabel('僅顯示已歸檔')
                    ->falseLabel('僅顯示正常中'),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_members')
                    ->label('指派成員')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('選擇使用者')
                            ->options(function () {
                                return User::all()->mapWithKeys(function ($user) {
                                    return [$user->id => "{$user->name} ({$user->account})"];
                                });
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->label('角色')
                            ->options([
                                'parent' => '家長',
                                'child' => '兒童',
                                'viewer' => '觀察者',
                            ])
                            ->default('child')
                            ->required(),
                    ])
                    ->action(function (Family $record, array $data) {
                        $userId = $data['user_id'];
                        $role = $data['role'];

                        // Check if already a member
                        if ($record->members()->where('user_id', $userId)->exists()) {
                            Notification::make()
                                ->title('該使用者已是此家庭成員')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->members()->attach($userId, [
                            'role' => $role,
                            'is_active' => true,
                            'joined_at' => now(),
                        ]);

                        // Set current_family_id if not set
                        $user = User::find($userId);
                        if ($user && !$user->current_family_id) {
                            $user->update(['current_family_id' => $record->id]);
                        }

                        Notification::make()
                            ->title('已成功將成員指派至家庭')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListFamilies::route('/'),
            'create' => Pages\CreateFamily::route('/create'),
            'edit' => Pages\EditFamily::route('/{record}/edit'),
        ];
    }
}
