@php
    // 判斷是否需要顯示「管理員唯讀模式」banner：
    //   1. 必須已登入且為系統管理員
    //   2. 必須已切換到某個家庭 (current_family_id != null)
    //   3. 必須不是該家庭的 parent (即未被邀請)
    //   4. 當前頁面不能是 admin/* 後台頁面 (後台頁面不屬於任何家庭)
    $shouldShowBanner = false;
    if (auth()->check()) {
        $user = auth()->user();
        $currentRouteName = optional(request()->route())->getName();
        $isAdminPage = $currentRouteName && (
            str_starts_with($currentRouteName, 'admin.') ||
            str_starts_with(request()->path(), 'admin/')
        );
        if (! $isAdminPage && $user->isReadOnlyViewerOfCurrentFamily()) {
            $shouldShowBanner = true;
        }
    }
@endphp

@if($shouldShowBanner)
    {{-- 管理員以唯讀模式檢視此家庭 --}}
    <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-lg flex items-start gap-3" role="alert">
        <span class="material-symbols-outlined text-amber-600 flex-shrink-0 mt-0.5">visibility</span>
        <div class="flex-1">
            <h4 class="font-bold text-amber-800">👀 管理員唯讀模式</h4>
            <p class="text-sm text-amber-700 mt-1">
                您目前以系統管理員身份檢視「<strong>{{ auth()->user()->currentFamily?->name }}</strong>」的資料，但<strong>無法新增、修改或刪除</strong>任何內容。
            </p>
            <p class="text-xs text-amber-600 mt-2">
                💡 若需要編輯此家庭的資料，請聯絡該家庭的<strong>家長</strong>，由家長透過「成員邀請」功能 (Email 或邀請碼) 將您加入此家庭，
                接受邀請後您即可獲得編輯權限。
            </p>
        </div>
    </div>
@endif
