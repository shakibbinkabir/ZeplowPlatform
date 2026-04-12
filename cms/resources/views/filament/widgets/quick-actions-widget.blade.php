<x-filament-widgets::widget>
    <x-filament::section heading="Quick Actions">
        <div class="flex flex-wrap items-center gap-3">
            {{ $this->resyncAllAction }}

            <x-filament::link href="https://zeplow.com" target="_blank" icon="heroicon-o-arrow-top-right-on-square">
                View Parent Site
            </x-filament::link>

            <x-filament::link href="https://narrative.zeplow.com" target="_blank" icon="heroicon-o-arrow-top-right-on-square">
                View Narrative Site
            </x-filament::link>

            <x-filament::link href="https://logic.zeplow.com" target="_blank" icon="heroicon-o-arrow-top-right-on-square">
                View Logic Site
            </x-filament::link>
        </div>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>
