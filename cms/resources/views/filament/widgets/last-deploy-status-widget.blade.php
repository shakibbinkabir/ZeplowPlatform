<x-filament-widgets::widget>
    <x-filament::section heading="Last Deploy Status">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($sites as $site)
                <div class="rounded-lg border p-4 {{ $site['last_sync_status'] === 'failed' ? 'border-danger-500 bg-danger-50 dark:bg-danger-950' : 'border-gray-200 dark:border-gray-700' }}">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $site['name'] }}</h3>

                    @if ($site['last_success_at'])
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            Last sync: {{ $site['last_success_at'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $site['last_success_time'] }}
                        </p>
                    @else
                        <p class="mt-1 text-sm text-gray-500">No syncs yet</p>
                    @endif

                    @if ($site['last_sync_status'] === 'failed')
                        <p class="mt-2 text-xs text-danger-600 dark:text-danger-400">
                            Last sync failed: {{ \Illuminate\Support\Str::limit($site['last_sync_error'], 100) }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
