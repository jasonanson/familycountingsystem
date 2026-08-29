<!-- Attachment / Invoice Viewer Modal (Google Stitch Design) -->
<div x-data="attachmentViewer()"
     x-show="showViewer"
     x-cloak
     @keydown.escape.window="showViewer = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title" role="dialog" aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showViewer" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showViewer = false" 
         class="fixed inset-0 bg-on-surface/50 backdrop-blur-md transition-opacity"></div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-6">
        <!-- Modal Card -->
        <div x-show="showViewer" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block bg-surface-pure border border-border-base rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all max-w-4xl w-full max-h-[90vh] flex flex-col my-8">

            <!-- Modal Header -->
            <div class="px-6 py-4 bg-surface-container border-b border-border-base flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-2xl">receipt_long</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface flex items-center gap-2" id="modal-title">
                            <span>{{ __('auto.0521') }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold bg-primary/10 text-primary" x-text="activeTx?.type === 'expense' ? '支出單據' : '收入單據'"></span>
                        </h3>
                        <p class="text-xs text-on-surface-variant">{{ __('auto.0437') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="activeTx?.attachmentUrl" download target="_blank" class="p-2 text-on-surface-variant hover:text-primary hover:bg-surface-container-highest rounded-xl transition-colors" title="{{ __('auto.0091') }}">
                        <span class="material-symbols-outlined text-xl">download</span>
                    </a>
                    <button type="button" @click="showViewer = false; resetView();" class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest rounded-xl transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Split Preview & Context Panel) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 overflow-hidden flex-1 min-h-[420px]">
                
                <!-- Left Column: Image Viewer with Floating Toolbar -->
                <div class="lg:col-span-8 bg-on-surface/5 dark:bg-black/40 p-6 flex flex-col items-center justify-center relative overflow-hidden group select-none min-h-[350px]">
                    
                    <!-- Floating Controls Toolbar -->
                    <div class="absolute top-4 right-4 z-10 bg-surface-pure/90 backdrop-blur border border-border-base rounded-xl p-1.5 shadow-md flex items-center gap-1">
                        <button type="button" @click="zoomIn()" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface" title="{{ __('auto.0378') }}">
                            <span class="material-symbols-outlined text-lg">zoom_in</span>
                        </button>
                        <button type="button" @click="zoomOut()" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface" title="{{ __('auto.0595') }}">
                            <span class="material-symbols-outlined text-lg">zoom_out</span>
                        </button>
                        <button type="button" @click="rotateRight()" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface" title="{{ __('auto.0234') }}">
                            <span class="material-symbols-outlined text-lg">rotate_right</span>
                        </button>
                        <button type="button" @click="resetView()" class="p-1.5 hover:bg-surface-container rounded-lg text-on-surface" title="{{ __('auto.0700') }}">
                            <span class="material-symbols-outlined text-lg">restart_alt</span>
                        </button>
                    </div>

                    <!-- Image / Attachment Content -->
                    <template x-if="activeTx?.attachmentUrl">
                        <div class="w-full h-full flex items-center justify-center overflow-auto p-4">
                            <template x-if="isImageUrl(activeTx?.attachmentUrl)"><img :src="activeTx?.attachmentUrl" alt="發票單據" class="max-h-[60vh] max-w-full object-contain rounded-lg border border-border-base shadow-md transition-transform duration-200" :style="`transform: scale(${zoom}) rotate(${rotate}deg);`" /></template><template x-if="isPdfUrl(activeTx?.attachmentUrl)"><iframe :src="activeTx?.attachmentUrl" class="w-full rounded-lg border border-border-base shadow-md bg-white" style="height: 60vh;" title="PDF 附件"></iframe></template><template x-if="!isImageUrl(activeTx?.attachmentUrl) && !isPdfUrl(activeTx?.attachmentUrl)"><div class="text-center text-on-surface-variant p-8 space-y-3"><span class="material-symbols-outlined text-5xl text-outline/50">description</span><p class="text-sm font-semibold">此檔案類型無法在視窗中預覽</p><a :href="activeTx?.attachmentUrl" download target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-on-primary font-bold rounded-xl text-xs hover:bg-primary/90 transition-colors"><span class="material-symbols-outlined text-base">download</span><span>下載檔案</span></a></div></template>
                        </div>
                    </template>
                    <template x-if="!activeTx?.attachmentUrl">
                        <div class="text-center text-on-surface-variant p-8">
                            <span class="material-symbols-outlined text-5xl text-outline/50 mb-2">no_photography</span>
                            <p class="text-sm font-semibold">{{ __('auto.0446') }}</p>
                        </div>
                    </template>
                </div>

                <!-- Right Column: Transaction Details Sidebar -->
                <div class="lg:col-span-4 p-6 bg-surface-pure border-t lg:border-t-0 lg:border-l border-border-base flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant border-b border-border-base pb-2 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base text-primary">info</span>
                            <span>{{ __('auto.0104') }}</span>
                        </h4>

                        <!-- Amount Display -->
                        <div class="p-4 rounded-xl bg-background-warm border border-border-base">
                            <div class="text-xs text-on-surface-variant font-bold mb-1">交易金額</div>
                            <div class="text-2xl font-black" :class="activeTx?.type === 'expense' ? 'text-danger' : 'text-success'">
                                <span x-text="activeTx?.type === 'expense' ? '-' : '+'"></span>
                                NT$ <span x-text="Number(activeTx?.amount || 0).toLocaleString()"></span>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-1.5 border-b border-border-base/50">
                                <span class="text-on-surface-variant font-medium">{{ __('auto.0466') }}</span>
                                <span class="font-bold text-on-surface" x-text="activeTx?.category || '未分類'"></span>
                            </div>

                            <div class="flex justify-between items-center py-1.5 border-b border-border-base/50">
                                <span class="text-on-surface-variant font-medium">{{ __('auto.0711') }}</span>
                                <span class="font-bold text-on-surface" x-text="activeTx?.account || '無'"></span>
                            </div>

                            <div class="flex justify-between items-center py-1.5 border-b border-border-base/50">
                                <span class="text-on-surface-variant font-medium">{{ __('auto.0100') }}</span>
                                <span class="font-mono text-xs text-on-surface font-bold" x-text="activeTx?.occurred_at || '-'"></span>
                            </div>

                            <div class="flex justify-between items-center py-1.5 border-b border-border-base/50">
                                <span class="text-on-surface-variant font-medium">{{ __('auto.0626') }}</span>
                                <span class="font-bold text-primary" x-text="activeTx?.user || '系統'"></span>
                            </div>

                            <template x-if="activeTx?.payee">
                                <div class="flex justify-between items-center py-1.5 border-b border-border-base/50">
                                    <span class="text-on-surface-variant font-medium">{{ __('auto.0099') }}</span>
                                    <span class="font-bold text-warning" x-text="activeTx?.payee"></span>
                                </div>
                            </template>

                            <template x-if="activeTx?.description">
                                <div class="pt-2">
                                    <div class="text-xs text-on-surface-variant font-bold mb-1">備註說明</div>
                                    <div class="p-3 bg-surface-container rounded-xl text-xs text-on-surface leading-relaxed border border-border-base/40" x-text="activeTx?.description"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-4 border-t border-border-base flex items-center justify-between">
                        <a :href="activeTx?.attachmentUrl" target="_blank" class="text-xs text-primary font-bold hover:underline inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            <span>{{ __('auto.0250') }}</span>
                        </a>
                        <button type="button" @click="showViewer = false; resetView();" class="px-4 py-2 bg-surface-container hover:bg-surface-variant text-on-surface font-bold text-xs rounded-xl transition-colors">
                            關閉視窗
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function attachmentViewer() {
    return {
        zoom: 1,
        rotate: 0,
        _imgExts: ['jpg','jpeg','png','gif','webp','heic','heif','bmp','svg'],
        resetView() { this.zoom = 1; this.rotate = 0; },
        zoomIn() { this.zoom = Math.min(this.zoom + 0.25, 3); },
        zoomOut() { this.zoom = Math.max(this.zoom - 0.25, 0.5); },
        rotateRight() { this.rotate = (this.rotate + 90) % 360; },
        _ext(url) {
            if (!url) return '';
            const m = String(url).toLowerCase().match(/\.([a-z0-9]+)(?:\?|#|$)/);
            return m ? m[1] : '';
        },
        isImageUrl(url) { return this._imgExts.includes(this._ext(url)); },
        isPdfUrl(url)   { return this._ext(url) === 'pdf'; }
    };
}
</script>
