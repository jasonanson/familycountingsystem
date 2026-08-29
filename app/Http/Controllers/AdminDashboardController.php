<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class AdminDashboardController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    public function index()
    {
        $this->checkAdmin();

        $totalFamilies = Family::count();
        $totalUsers = User::count();
        $totalTransactions = Transaction::count();
        
        // Calculate real storage disk usage from storage/app
        $storagePath = storage_path('app');
        $bytes = 0;
        if (file_exists($storagePath)) {
            try {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storagePath, FilesystemIterator::SKIP_DOTS)) as $file) {
                    $bytes += $file->getSize();
                }
            } catch (\Throwable $e) {
                $bytes = 10485760; // 10 MB fallback
            }
        }
        
        if ($bytes >= 1073741824) {
            $storageUsedFormatted = number_format($bytes / 1073741824, 2) . ' GB';
        } else {
            $storageUsedFormatted = number_format(max(0.1, $bytes / 1048576), 1) . ' MB';
        }
        
        // Percentage based on 5GB total limit
        $storagePercent = min(100, max(1, round(($bytes / (5 * 1073741824)) * 100, 1)));
        
        $recentFamilies = Family::latest()->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalFamilies',
            'totalUsers',
            'totalTransactions',
            'storageUsedFormatted',
            'storagePercent',
            'recentFamilies',
            'recentUsers'
        ));
    }
}
