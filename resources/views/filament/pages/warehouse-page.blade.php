<x-filament-panels::page>
    <x-filament::tabs label="Quản lý kho">
        <x-filament::tabs.item
            :active="$activeTab === 'stock'"
            wire:click="$set('activeTab', 'stock')"
            icon="heroicon-o-archive-box"
        >
            Tồn kho
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'import-export'"
            wire:click="$set('activeTab', 'import-export')"
            icon="heroicon-o-arrows-right-left"
        >
            Nhập / Xuất
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- TAB 1: Tồn kho hiện tại --}}
    @if($activeTab === 'stock')
        {{ $this->table }}

        {{-- Tra cứu tồn tại 1 thời điểm --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">Tra cứu tồn kho tại 1 thời điểm</x-slot>
            {{ $this->stockAtForm }}
            <div class="mt-4">
                <x-filament::button wire:click="submitStockAt" color="info" icon="heroicon-o-magnifying-glass">
                    Tra cứu
                </x-filament::button>
            </div>
            @if($stockAtResult)
                <div class="mt-6 grid grid-cols-2 gap-6 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Nguyên liệu</p>
                        <p class="font-semibold text-lg">{{ $stockAtResult['ingredient'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Thời điểm</p>
                        <p class="font-semibold">{{ $stockAtResult['at'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tồn kho</p>
                        <p class="font-bold text-2xl text-primary-600">
                            {{ number_format($stockAtResult['stock'], 2) }} {{ $stockAtResult['unit'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Giá bình quân lúc đó</p>
                        <p class="font-bold text-2xl">{{ number_format($stockAtResult['cost_price'], 0, ',', '.') }}₫</p>
                    </div>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- TAB 2: Nhập / Xuất --}}
    @if($activeTab === 'import-export')

        {{-- Báo cáo tổng nhập/xuất --}}
        <x-filament::section>
            <x-slot name="heading">Báo cáo tổng nhập / xuất</x-slot>
            {{ $this->reportForm }}
            <div class="mt-4">
                <x-filament::button wire:click="submitReport" color="warning" icon="heroicon-o-document-chart-bar">
                    Xuất báo cáo
                </x-filament::button>
            </div>
            @if($reportResult)
                <div class="mt-6 overflow-x-auto">
                    <p class="text-sm text-gray-500 mb-3">Từ {{ $reportResult['from'] }} đến {{ $reportResult['until'] }}</p>
                    <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @foreach(['Nguyên liệu','Tổng nhập','Tổng xuất','Chênh lệch','Tồn hiện tại'] as $col)
                                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($reportResult['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-success-600 font-medium">+{{ number_format($row['total_import'], 2) }} {{ $row['unit'] }}</td>
                                    <td class="px-4 py-3 text-danger-600 font-medium">-{{ number_format($row['total_export'], 2) }} {{ $row['unit'] }}</td>
                                    <td class="px-4 py-3 font-semibold {{ $row['diff'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $row['diff'] >= 0 ? '+' : '' }}{{ number_format($row['diff'], 2) }} {{ $row['unit'] }}
                                    </td>
                                    <td class="px-4 py-3">{{ number_format($row['stock_now'], 2) }} {{ $row['unit'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Lịch sử nhập kho chi tiết --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">Lịch sử nhập kho</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach(['Thời gian','Phiếu nhập','Nguyên liệu','SL nhập','Tồn trước','Tồn sau','Giá nhập','Giá BQ trước','Giá BQ sau','Biến động'] as $col)
                                <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($this->getImportLogs() as $log)
                            @php $diff = $log->cost_price_after - $log->cost_price_before; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $log->imported_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $log->import_order_code }}</td>
                                <td class="px-4 py-3 font-medium">{{ $log->ingredient?->name }}</td>
                                <td class="px-4 py-3">{{ number_format($log->quantity, 2) }} {{ $log->ingredient?->unit?->symbol }}</td>
                                <td class="px-4 py-3">{{ number_format($log->stock_before, 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->stock_after, 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($log->unit_price, 0, ',', '.') }}₫</td>
                                <td class="px-4 py-3">{{ number_format($log->cost_price_before, 0, ',', '.') }}₫</td>
                                <td class="px-4 py-3">{{ number_format($log->cost_price_after, 0, ',', '.') }}₫</td>
                                <td class="px-4 py-3">
                                    @if($diff > 0)
                                        <span class="text-danger-600 font-medium">▲ {{ number_format($diff, 0, ',', '.') }}₫</span>
                                    @elseif($diff < 0)
                                        <span class="text-success-600 font-medium">▼ {{ number_format(abs($diff), 0, ',', '.') }}₫</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-400">Chưa có lịch sử nhập kho</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $this->getImportLogs()->links() }}</div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
