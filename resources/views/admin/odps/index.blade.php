@extends('layouts.admin', ['title' => 'Master Data ODP'])

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ editingOdp: null }">
    <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Master Data ODP & Mode Koneksi</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Atur daftar ODP lapangan. Saat tim lapangan selesai memigrasikan jalur ke PPPoE, tekan tombol ubah status pada baris ODP agar kiriman PSB berikutnya langsung membuat PPPoE Secret.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5 xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah ODP Baru</h2>
            <form method="POST" action="{{ route('admin.odps.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="nama_odp" class="block text-xs font-bold uppercase text-[#06233B]">Nama ODP (Label Lapangan)</label>
                    <input
                        id="nama_odp"
                        name="nama_odp"
                        type="text"
                        required
                        placeholder="Contoh: U15 B"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div>
                    <label for="kode_odp" class="block text-xs font-bold uppercase text-[#06233B]">Kode ODP (Awalan PPPoE)</label>
                    <input
                        id="kode_odp"
                        name="kode_odp"
                        type="text"
                        required
                        placeholder="Contoh: 12"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div>
                    <label for="status_upgrade" class="block text-xs font-bold uppercase text-[#06233B]">Status Upgrade</label>
                    <select
                        id="status_upgrade"
                        name="status_upgrade"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                        <option value="belum_upgrade">Belum Upgrade [Static IP]</option>
                        <option value="upgrade">Sudah Upgrade [PPPoE]</option>
                    </select>
                </div>

                <div>
                    <label for="last_seq" class="block text-xs font-bold uppercase text-[#06233B]">Nomor Urut Terakhir PPPoE (last_seq)</label>
                    <input
                        id="last_seq"
                        name="last_seq"
                        type="number"
                        min="0"
                        value="0"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                    Simpan ODP Baru
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-4 py-3.5">Nama ODP</th>
                            <th class="px-4 py-3.5">Kode PPPoE</th>
                            <th class="px-4 py-3.5">Status & Ubah Cepat</th>
                            <th class="px-4 py-3.5">Last Seq</th>
                            <th class="px-4 py-3.5">Total PSB</th>
                            <th class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#B8D4EC] bg-white">
                        @forelse ($odps as $odp)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3 font-extrabold text-[#06233B]">{{ $odp->nama_odp }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-[#005F99]">{{ $odp->kode_odp }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($odp->status_upgrade === 'upgrade')
                                    <span class="inline-flex rounded-md border border-[#0284C7] bg-[#E0F2FE] px-2.5 py-1 font-mono text-xs font-bold text-[#075985]">
                                        [PPPoE] UPGRADE
                                    </span>
                                    @else
                                    <span class="inline-flex rounded-md border border-[#D97706] bg-[#FEF3C7] px-2.5 py-1 font-mono text-xs font-bold text-[#78350F]">
                                        [IP] BELUM UPGRADE
                                    </span>
                                    @endif

                                    <form method="POST" action="{{ route('admin.odps.toggle-upgrade', $odp) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#005F99] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                            {{ $odp->status_upgrade === 'upgrade' ? 'Ubah ke [IP]' : 'Set [PPPoE] Upgrade' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#2C5270]">
                                {{ str_pad((string) $odp->last_seq, 5, '0', STR_PAD_LEFT) }} ({{ $odp->last_seq }})
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $odp->psb_orders_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingOdp = @js($odp)"
                                        class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-2 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.odps.destroy', $odp) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus ODP {{ $odp->nama_odp }}?');">
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
                                Belum ada data ODP terdaftar. Tambahkan ODP baru melalui formulir di sebelah kiri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="editingOdp !== null"
        x-cloak
        @keydown.escape.window="editingOdp = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true">
        <template x-if="editingOdp">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
                <h3 class="text-base font-extrabold text-[#06233B]" x-text="'Edit ODP: ' + editingOdp.nama_odp"></h3>
                <form method="POST" :action="'/admin/odps/' + editingOdp.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Nama ODP</label>
                        <input
                            type="text"
                            name="nama_odp"
                            x-model="editingOdp.nama_odp"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Kode ODP</label>
                        <input
                            type="text"
                            name="kode_odp"
                            x-model="editingOdp.kode_odp"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Status Upgrade</label>
                        <select
                            name="status_upgrade"
                            x-model="editingOdp.status_upgrade"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                            <option value="belum_upgrade">Belum Upgrade [Static IP]</option>
                            <option value="upgrade">Sudah Upgrade [PPPoE]</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Last Sequence PPPoE</label>
                        <input
                            type="number"
                            name="last_seq"
                            min="0"
                            x-model="editingOdp.last_seq"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingOdp = null"
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