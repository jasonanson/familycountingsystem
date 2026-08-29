<x-app-layout>
    <x-slot name="title">Budget Details</x-slot>

    <div class="container mx-auto p-4 bg-background-warm text-on-surface">
        <h1 class="text-2xl font-bold flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined">visibility</span>
            Budget Details (ID: {{ $budget->id }})
        </h1>
        <div class="p-6 bg-surface-pure rounded-xl shadow-md border border-border-base space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-bold text-on-surface-variant">Period Type</h3>
                    <p class="text-lg font-bold text-on-surface">{{ ucfirst($budget->period_type) }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-on-surface-variant">Amount</h3>
                    <p class="text-lg font-bold text-primary">NT$ {{ number_format($budget->amount) }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-on-surface-variant">Date Range</h3>
                    <p class="text-on-surface">
                        {{ $budget->period_start?->format('Y-m-d') ?? 'N/A' }} ~ 
                        {{ $budget->period_end?->format('Y-m-d') ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-on-surface-variant">Scope</h3>
                    <p class="text-on-surface">{{ ucfirst($budget->scope ?? 'N/A') }}</p>
                </div>
            </div>
            
            <div class="pt-4 border-t border-border-base mt-4">
                <a href="{{ route('budgets.index') }}" class="text-sm text-primary font-bold hover:underline">
                    &larr; Back to Budgets
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
