<?php

use Livewire\Component;

new class extends Component {
    public string $nextReset;

    public function mount(): void
    {
        $now = now();
        $nextHour = $now->copy()->startOfHour();

        if ($nextHour->hour % 2 !== 0) {
            $nextHour->addHour();
        }

        if ($nextHour->lte($now)) {
            $nextHour->addHours(2);
        }

        $this->nextReset = $nextHour->toIso8601String();
    }
}; ?>

<div
    x-data="{
        nextReset: new Date('{{ $nextReset }}'),
        remaining: '',
        init() {
            this.update()
            setInterval(() => this.update(), 1000)
        },
        update() {
            const diff = Math.max(0, this.nextReset - Date.now())
            const h = Math.floor(diff / 3600000)
            const m = Math.floor((diff % 3600000) / 60000)
            const s = Math.floor((diff % 60000) / 1000)
            this.remaining = `${h}h ${m}m ${s}s`
        }
    }"
>
    This is a demo environment. Data resets every 2 hours. Next reset in <span x-text="remaining"></span>
</div>
