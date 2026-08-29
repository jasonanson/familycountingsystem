<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = '系統與家庭管理';

    protected static ?string $modelLabel = '使用者';

    protected static ?string $pluralModelLabel = '使用者列表';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('帳號個人資料')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                    ->label('頭像')
                    ->image()
                    ->avatar()
                    ->directory('avatars')
                    ->disk('public')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth(200)
                    ->imageResizeTargetHeight(200),

                Forms\Components\TextInput::make('name')
                            ->label('姓名/顯示名稱')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('account')
                            ->label('登入帳號')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('電話')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Forms\Components\Select::make('current_family_id')
                            ->label('目前選取家庭')
                            ->relationship('currentFamily', 'name')
                            ->nullable(),

                        Forms\Components\Toggle::make('is_system_admin')
                            ->label('系統管理員 (SuperAdmin 跨家庭全權)')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('頭像')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=14b8a6&background=e0f2f1')
                    ->size(40),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account')
                    ->label('帳號')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('currentFamily.name')
                    ->label('預算家庭')
                    ->placeholder('未設定'),

                Tables\Columns\IconColumn::make('is_system_admin')
                    ->label('全域最高管理員')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_system_admin')
                    ->label('管理員角色')
                    ->trueLabel('僅顯示系統管理員')
                    ->falseLabel('僅顯示一般成員'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
