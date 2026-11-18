@extends('layouts.admin', ['title' => 'Master Data Sales'])

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ editingSale: null }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Master Data Sales & Pemetaan Router</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Hubungkan setiap kode sales (<code class="rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono font-bold text-[#075985]">PAY, KD, PN, JN, JB</code>) dengan kode 2 digit PPPoE dan router distribusi Static IP yang sesuai.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5 xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah Sales Baru</h2>
            <form method="POST" action="{{ route('admin.sales.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="nama_sales" class="block text-xs font-bold uppercase text-[#06233B]">Nama Sales</label>
                    <input
                        id="nama_sales"
                        type="text"
                        name="nama_sales"
                        required
                        placeholder="Contoh: Sales PAY"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="kode_sales_ip" class="block text-xs font-bold uppercase text-[#06233B]">Kode Sales IP</label>
                        <input
                            id="kode_sales_ip"
                            type="text"
                            name="kode_sales_ip"
                            required
                            placeholder="PAY / KD / PN"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold uppercase text-[#06233B]">
                    </div>
                    <div>
                        <label for="kode_sales_pppoe" class="block text-xs font-bold uppercase text-[#06233B]">Kode Sales PPPoE</label>
                        <input
                            id="kode_sales_pppoe"
                            type="text"
                            name="kode_sales_pppoe"
                            required
                            placeholder="01 / 02 / 03"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                </div>

                <div>
                    <label for="ip_router_id" class="block text-xs font-bold uppercase text-[#06233B]">Router Distribusi Static IP</label>
                    <select
                        id="ip_router_id"
                        name="ip_router_id"
                        required
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                        <option value="">-- Pilih Router Distribusi --</option>
                        @foreach ($distributionRouters as $router)
                        <option value="{{ $router->id }}">{{ $router->nama_router }} ({{ $router->ip_host }})</option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                    Simpan Sales
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Kode IP</th>
                            <th scope="col" class="px-4 py-3.5">Kode PPPoE</th>
                            <th scope="col" class="px-4 py-3.5">Nama Sales</th>
                            <th scope="col" class="px-4 py-3.5">Router Distribusi</th>
                            <th scope="col" class="px-4 py-3.5">Total PSB</th>
                            <th scope="col" class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7FA6C9] bg-white">
                        @forelse ($salesList as $sale)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3 font-mono font-extrabold text-[#005F99]">{{ $sale->kode_sales_ip }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-[#06233B]">{{ $sale->kode_sales_pppoe }}</td>
                            <td class="px-4 py-3 font-bold text-[#06233B]">{{ $sale->nama_sales }}</td>
                            <td class="px-4 py-3 text-xs font-semibold text-[#06233B]">
                                {{ $sale->ipRouter?->nama_router ?? '-' }}
                                <span class="font-mono text-[#2C5270]">({{ $sale->ipRouter?->ip_host }})</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $sale->psb_orders_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingSale = @js($sale)"
                                        class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.sales.destroy', $sale) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus sales {{ $sale->kode_sales_ip }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border border-[#DC2626] bg-[#FEE2E2] px-3 py-2 font-bold text-[#991B1B] hover:bg-[#FECACA]">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-[#2C5270]">
                                Belum ada data Sales terdaftar. Tambahkan kode sales melalui formulir di sebelah kiri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="editingSale !== null"
        x-cloak
        x-trap.inert.noscroll="editingSale !== null"
        @keydown.escape.window="editingSale = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-sale-modal-title">
        <template x-if="editingSale">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
                <h3 id="edit-sale-modal-title" class="text-base font-extrabold text-[#06233B]" x-text="'Edit Sales: ' + editingSale.kode_sales_ip"></h3>
                <form method="POST" :action="'/admin/sales/' + editingSale.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_nama_sales" class="block text-xs font-bold uppercase text-[#06233B]">Nama Sales</label>
                        <input
                            id="edit_nama_sales"
                            type="text"
                            name="nama_sales"
                            x-model="editingSale.nama_sales"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="edit_kode_sales_ip" class="block text-xs font-bold uppercase text-[#06233B]">Kode Sales IP</label>
                            <input
                                id="edit_kode_sales_ip"
                                type="text"
                                name="kode_sales_ip"
                                x-model="editingSale.kode_sales_ip"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold uppercase text-[#06233B]">
                        </div>
                        <div>
                            <label for="edit_kode_sales_pppoe" class="block text-xs font-bold uppercase text-[#06233B]">Kode Sales PPPoE</label>
                            <input
                                id="edit_kode_sales_pppoe"
                                type="text"
                                name="kode_sales_pppoe"
                                x-model="editingSale.kode_sales_pppoe"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                        </div>
                    </div>
                    <div>
                        <label for="edit_ip_router_id" class="block text-xs font-bold uppercase text-[#06233B]">Router Distribusi</label>
                        <select
                            id="edit_ip_router_id"
                            name="ip_router_id"
                            x-model="editingSale.ip_router_id"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            @foreach ($distributionRouters as $router)
                            <option value="{{ $router->id }}">{{ $router->nama_router }} ({{ $router->ip_host }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingSale = null"
                            class="min-h-[44px] rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-4 py-2 text-xs font-bold text-[#06233B]">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-[44px] rounded-lg bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</div>
@endsection
