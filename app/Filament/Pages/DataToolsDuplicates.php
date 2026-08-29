<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DataToolsDuplicates extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.data-tools-duplicates';

    protected static ?string $navigationGroup = '資料工具';

    protected static ?string $title = '重複資料檢查';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->user()?->is_system_admin ?? false;
    }

    public function getDuplicateEmails()
    {
        return User::query()
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }

    public function getDuplicateAccounts()
    {
        return User::query()
            ->select('account', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('account')
            ->groupBy('account')
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }

    public function getUsersByEmail(string $email)
    {
        return User::where('email', $email)->get();
    }

    public function getUsersByAccount(string $account)
    {
        return User::where('account', $account)->get();
    }
}