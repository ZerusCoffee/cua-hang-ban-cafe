<div class="flex items-center gap-2 border-black">
    <input
        type="date"
        id="lookup_date"
        value="{{ $this->lookupDate ?? now()->format('d/m/Y') }}"
        class="rounded-lg border-gray-300 text-sm"
        onchange="Livewire.dispatch('updateDate', { date: this.value })"
</div>
