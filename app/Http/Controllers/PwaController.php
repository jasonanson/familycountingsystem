<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PwaController extends Controller
{
    /**
     * 回傳 PWA Web App Manifest JSON
     */
    public function manifest()
    {
        $path = public_path('manifest.json');
        if (File::exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        }

        return response()->json([
            'name' => 'HomeSync Finance',
            'short_name' => 'HomeSync',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#FAFAF9',
            'theme_color' => '#006b5f',
        ]);
    }

    /**
     * 回傳 Service Worker 程式腳本
     */
    public function serviceWorker()
    {
        $path = public_path('sw.js');
        if (File::exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'application/javascript; charset=utf-8'
            ]);
        }

        return response('', 404);
    }

    /**
     * 回傳 PWA 離線專用 fallback 頁面
     */
    public function offline()
    {
        return view('pwa.offline');
    }
}
