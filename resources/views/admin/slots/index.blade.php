@extends('layouts.admin', ['title' => 'Slot Monitor & Sinkronisasi IP'])

@section('content')
<div class="space-y-5">
    <!-- Top Header & Sync Action -->
    <div class="flex flex-col justify-between gap-4 rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:p-5">
        <div>
            <h1 class="text-lg font-extrabold text-[#06233B] sm:text-xl">Monitor Slot IP Simple Queue</h1>
            <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
                Memantau antrean Simple Queue berpola <code class="rounded bg-[#EDF5FC] px-1.5 py-0.5 font-mono font-bold text-[#005F99]">CLIENT-*</code> yang bebas Static ARP dan tidak terisolir di <code class="rounded bg-[#EDF5FC] px-1.5 py-0.5 font-mono font-bold text-[#005F99]">EXPIRED</code>.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.slots.sync') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-[#005F99] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-[#054A78]">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Sync Semua Router Sekarang</span>
            </button>
        </form>
    </div>

    <!-- Desktop 12-Col Split: Left 4 Cols (Per-Router Summary Cards), Right 8 Cols (Filter + Detailed Slot Table) -->
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
        <!-- Left 4 Columns: Distribution Routers List -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:col-span-4 xl:grid-cols-1">
            @forelse ($distributionRouters as $router)
            <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-extrabold text-[#06233B]">{{ $router->nama_router }}</h2>
                        <p class="font-mono text-xs text-[#2C5270]">
                            {{ $router->ip_host }}:{{ $router->api_port }} ({{ $router->lan_interface ?? '-' }})
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.slots.sync') }}">
                        @csrf
                        <input type="hidden" name="router_id" value="{{ $router->id }}">
                        <button
                            type="submit"
                            class="inline-flex min-h-[40px] items-center rounded-lg border-[1.5px] border-[#005F99] bg-[#E0F2FE] px-3 py-1.5 text-xs font-bold text-[#075985] hover:bg-[#005F99] hover:text-white">
                            Sync Router Ini
                        </button>
                    </form>
                </div>

                <div class="mt-2 text-xs text-[#2C5270]">
                    Sales Terhubung:
                    @if ($router->sales->isNotEmpty())
                    <strong class="font-mono text-[#06233B]">{{ $router->sales->pluck('kode_sales_ip')->join(', ') }}</strong>
                    @else
                    <span class="italic">Belum dipetakan</span>
                    @endif
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2.5 border-t border-[#B8D4EC] pt-3">
                    <div class="rounded-xl border border-[#059669] bg-[#D1FAE5] p-2.5">
                        <div class="text-[11px] font-bold uppercase text-[#065F46]">Available</div>
                        <div class="font-mono text-lg font-extrabold text-[#065F46]">{{ $router->available_slots_count }}</div>
                    </div>
                    <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-2.5">
                        <div class="text-[11px] font-bold uppercase text-[#2C5270]">Used</div>
                        <div class="font-mono text-lg font-extrabold text-[#06233B]">{{ $router->used_slots_count }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-6 text-center text-sm text-[#2C5270]">
                Belum ada router distribusi (non-PPPoE) yang terdaftar. Tambahkan terlebih dahulu di menu <strong>Master Router</strong>.
            </div>
            @endforelse
        </div>

        <!-- Right 8 Columns: Filter + Slot Table -->
        <div class="space-y-4 xl:col-span-8">
            <form method="GET" action="{{ route('admin.slots.index') }}" class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="router_id" class="block text-xs font-bold uppercase text-[#06233B]">Pilih Router</label>
                        <select
                            id="router_id"
                            name="router_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                            <option value="">Semua Router Distribusi</option>
                            @foreach ($distributionRouters as $r)
                            <option value="{{ $r->id }}" @selected($filters['router_id']===(string) $r->id)>
                                {{ $r->nama_router }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase text-[#06233B]">Status Slot</label>
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                            <option value="">Semua Status</option>
                            <option value="available" @selected($filters['status']==='available' )>Available (Kosong)</option>
                            <option value="used" @selected($filters['status']==='used' )>Used (Terpakai)</option>
                        </select>
                    </div>

                    <div>
                        <label for="q" class="block text-xs font-bold uppercase text-[#06233B]">Cari IP / Queue</label>
                        <input
                            id="q"
                            name="q"
                            type="text"
                            value="{{ $filters['q'] }}"
                            placeholder="172.16.103 atau CLIENT-"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
                            Filter Slot
                        </button>
                        <a
                            href="{{ route('admin.slots.index') }}"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                        <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                            <tr>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5">IP Address</th>
                                <th class="px-4 py-3.5">Simple Queue Name</th>
                                <th class="px-4 py-3.5">Router Distribusi</th>
                                <th class="px-4 py-3.5">Queue .id</th>
                                <th class="px-4 py-3.5">Order PSB Terkait</th>
                                <th class="px-4 py-3.5">Sinkronisasi Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#B8D4EC] bg-white">
                            @forelse ($slots as $slot)
                            <tr class="hover:bg-[#EDF5FC]/70">
                                <td class="px-4 py-3">
                                    @if ($slot->status === 'available')
                                    <span class="inline-flex rounded-md border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono text-xs font-bold text-[#065F46]">
                                        AVAILABLE
                                    </span>
                                    @else
                                    <span class="inline-flex rounded-md border border-[#5B82A6] bg-[#EDF5FC] px-2 py-0.5 font-mono text-xs font-bold text-[#06233B]">
                                        USED
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono font-extrabold text-[#005F99]">{{ $slot->ip_address }}</td>
                                <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $slot->queue_name }}</td>
                                <td class="px-4 py-3 font-semibold text-[#06233B]">{{ $slot->router?->nama_router }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-[#2C5270]">{{ $slot->queue_mk_id }}</td>
                                <td class="px-4 py-3 text-xs text-[#2C5270]">
                                    @if ($slot->psbOrder)
                                    <strong class="text-[#06233B]">{{ $slot->psbOrder->nama_pelanggan }}</strong>
                                    <span>(#{{ $slot->psbOrder->id }})</span>
                                    @else
                                    <span>Tidak ada order terkait</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#2C5270]">
                                    {{ $slot->updated_at?->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-[#2C5270]">
                                    Belum ada slot IP tersinkronisasi untuk kriteria ini. Klik tombol <strong>Sync Semua Router Sekarang</strong> di kanan atas untuk menarik data dari MikroTik.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($slots->hasPages())
                <div class="border-t border-[#B8D4EC] bg-[#EDF5FC] px-4 py-3">
                    {{ $slots->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection