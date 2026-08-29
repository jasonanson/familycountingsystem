@if(auth()->check() && auth()->user()->isReadOnlyViewerOfCurrentFamily())
    {{-- 在唯讀模式時,於原本「新增 / 編輯 / 刪除」按鈕的位置顯示提示 --}}
    <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1" title="您目前以管理員身份檢視此家庭,無法新增/修改/刪除資料">
        <span class="material-symbols-outlined text-[14px]">lock</span>
        <span>管理員唯讀</span>
    </div>
@endif
