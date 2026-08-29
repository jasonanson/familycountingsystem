@extends('layouts.app')
@section('title', 'OAuth 設定未完成')

@section('content')
<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-xl shadow p-6 border border-danger/20">
        <h1 class="text-xl font-bold text-danger mb-3">⚠️ OAuth 環境變數未設定</h1>
        <p class="text-sm text-on-surface-variant mb-3">{{ __('auto.0603') }}</p>
        <ul class="list-disc list-inside text-sm text-danger">
            @foreach ($missing as $k => $v)
                <li><code>{{ $k }}</code></li>
            @endforeach
        </ul>
        <p class="text-sm text-on-surface-variant mt-3">{{ __('auto.0636') }}</p>
    </div>
</div>
@endsection
        @isset($adminUrl)
        <div class="mt-6 text-center">
            <a href="{{ $adminUrl }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow">
                前往後台 Gmail 設定頁
            </a>
        </div>
        @endisset