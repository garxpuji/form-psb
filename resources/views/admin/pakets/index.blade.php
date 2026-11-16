@extends('layouts.admin', ['title' => 'Master Data Paket Internet'])

@section('content')
<div class="space-y-5" x-data="{ editingPaket: null }">
    <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-lg font-extrabold text-[#06233B] sm:text-xl">Master Data Paket Internet</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Atur daftar paket kecepatan pelanggan beserta nilai <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">max-limit</code> Simple Queue dan nama <code class="rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono font-bold text-[#075985]">profile</code> PPPoE di MikroTik.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
        <!-- Create Form (4 Columns on Desktop) -->
        <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-5 shadow-sm xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah Paket Baru</h2>
            <form method="POST" action="{{ route('admin.pakets.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Paket</label>
                    <input
                        type="text"
                        name="nama_paket"
                        required
                        placeholder="Contoh: 10mbps / 20mbps"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Simple Queue Max-Limit</label>
                    <input
                        type="text"
                        name="queue_max_limit"
                        required
                        placeholder="Contoh: 10M/10M"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Profile PPPoE MikroTik</label>
                    <input
                        type="text"
                        name="pppoe_profile"
                        required
                        placeholder="Contoh: PAKET-10M"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78]">
                    Simpan Paket
                </button>
            </form>
        </div>

        <!-- Pakets Table (8 Columns on Desktop) -->
        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-4 py-3.5">Nama Paket</th>
                            <th class="px-4 py-3.5">Simple Queue Max-Limit</th>
                            <th class="px-4 py-3.5">PPPoE Profile</th>
                            <th class="px-4 py-3.5">Total PSB</th>
                            <th class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#B8D4EC] bg-white">
                        @forelse ($pakets as $paket)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3 font-extrabold text-[#06233B]">{{ $paket->nama_paket }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono text-xs font-bold text-[#78350F]">
                                    {{ $paket->queue_max_limit }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-mono text-xs font-bold text-[#075985]">
                                    {{ $paket->pppoe_profile }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $paket->psb_orders_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingPaket = @js($paket)"
                                        class="inline-flex min-h-[40px] items-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-1.5 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.pakets.destroy', $paket) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus paket {{ $paket->nama_paket }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[40px] items-center rounded-lg border border-[#DC2626] bg-[#FEE2E2] px-3 py-1.5 font-bold text-[#991B1B] hover:bg-[#FECACA]">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-[#2C5270]">
                                Belum ada paket internet terdaftar. Tambahkan paket melalui formulir di sebelah kiri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div
        x-show="editingPaket !== null"
        x-cloak
        @keydown.escape.window="editingPaket = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true">
        <template x-if="editingPaket">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-6 shadow-2xl">
                <h3 class="text-base font-extrabold text-[#06233B]" x-text="'Edit Paket: ' + editingPaket.nama_paket"></h3>
                <form method="POST" :action="'/admin/pakets/' + editingPaket.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Paket</label>
                        <input
                            type="text"
                            name="nama_paket"
                            x-model="editingPaket.nama_paket"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Simple Queue Max-Limit</label>
                        <input
                            type="text"
                            name="queue_max_limit"
                            x-model="editingPaket.queue_max_limit"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">PPPoE Profile</label>
                        <input
                            type="text"
                            name="pppoe_profile"
                            x-model="editingPaket.pppoe_profile"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingPaket = null"
                            class="min-h-[44px] rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-4 py-2 text-xs font-bold text-[#06233B]">
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