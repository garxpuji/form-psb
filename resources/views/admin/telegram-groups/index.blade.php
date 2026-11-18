@extends('layouts.admin', ['title' => 'Master Grup Telegram'])

@section('content')
@php
    $hasWebhookSecret = trim((string) config('services.telegram.webhook_secret', '')) !== '';
@endphp
<div class="space-y-4 sm:space-y-5" x-data="{ editingGroup: null }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Master Grup Telegram PSB</h1>
                <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
                    Atur 1 grup utama <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">ALL TEAM PSB</code> dan grup khusus tiap tim teknisi (<code class="rounded bg-[#D1FAE5] px-1.5 py-0.5 font-mono font-bold text-[#065F46]">TEAM 1 s/d TEAM 4</code>).
                </p>
            </div>

            <div class="rounded-xl border border-[#0284C7] bg-[#E0F2FE] px-4 py-2.5 text-xs text-[#075985]">
                <div class="flex flex-wrap items-center justify-between gap-2 font-bold">
                    <span>Endpoint Webhook `/cekid`:</span>
                    @if ($hasWebhookSecret)
                        <span class="rounded border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono text-[11px] text-[#065F46]">SECRET TOKEN AKTIF</span>
                    @else
                        <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono text-[11px] text-[#78350F]">SET TELEGRAM_WEBHOOK_SECRET DI .ENV</span>
                    @endif
                </div>
                <div class="mt-1">
                    Daftarkan <code class="font-mono font-bold">{{ url('/api/telegram/webhook') }}</code> beserta parameter <code class="font-mono font-bold">secret_token</code> lalu kirim <code class="font-mono font-bold">/cekid</code> di grup.
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5 xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah Grup Telegram</h2>
            <form method="POST" action="{{ route('admin.telegram-groups.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="nama_grup" class="block text-xs font-bold uppercase text-[#06233B]">Nama Grup</label>
                    <input
                        id="nama_grup"
                        type="text"
                        name="nama_grup"
                        required
                        placeholder="Contoh: TEAM 1 atau ALL TEAM PSB"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                </div>

                <div>
                    <label for="chat_id" class="block text-xs font-bold uppercase text-[#06233B]">Chat ID</label>
                    <input
                        id="chat_id"
                        type="text"
                        name="chat_id"
                        required
                        placeholder="Contoh: -1001234567890"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <div>
                    <label for="thread_id" class="block text-xs font-bold uppercase text-[#06233B]">Thread ID / Topic (Opsional)</label>
                    <input
                        id="thread_id"
                        type="number"
                        name="thread_id"
                        placeholder="Kosongkan jika grup tidak memakai fitur Topics"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                    <label for="create_is_all_team" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                        <input
                            id="create_is_all_team"
                            type="checkbox"
                            name="is_all_team"
                            value="1"
                            class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                        <span>Jadikan Grup Utama ALL TEAM PSB</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                    Simpan Grup Telegram
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Nama Grup</th>
                            <th scope="col" class="px-4 py-3.5">Chat ID</th>
                            <th scope="col" class="px-4 py-3.5">Thread ID</th>
                            <th scope="col" class="px-4 py-3.5">Tipe Routing</th>
                            <th scope="col" class="px-4 py-3.5">Teknisi</th>
                            <th scope="col" class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7FA6C9] bg-white">
                        @forelse ($groups as $group)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3 font-extrabold text-[#06233B]">{{ $group->nama_grup }}</td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#005F99]">{{ $group->chat_id }}</td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#2C5270]">{{ $group->thread_id ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($group->is_all_team)
                                <span class="inline-flex rounded-md border border-[#D97706] bg-[#FEF3C7] px-2.5 py-0.5 font-mono text-xs font-bold text-[#78350F]">
                                    ALL TEAM PSB (Non-Tim)
                                </span>
                                @else
                                <span class="inline-flex rounded-md border border-[#059669] bg-[#D1FAE5] px-2.5 py-0.5 font-mono text-xs font-bold text-[#065F46]">
                                    TEAM PSB ONLY
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $group->users_count }} akun</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingGroup = @js($group)"
                                        class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    @if (! $group->is_all_team)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.telegram-groups.destroy', $group) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus grup {{ $group->nama_grup }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border border-[#DC2626] bg-[#FEE2E2] px-3 py-2 font-bold text-[#991B1B] hover:bg-[#FECACA]">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-[#2C5270]">
                                Belum ada grup Telegram terdaftar. Tambahkan grup menggunakan formulir di sebelah kiri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="editingGroup !== null"
        x-cloak
        x-trap.inert.noscroll="editingGroup !== null"
        @keydown.escape.window="editingGroup = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-group-modal-title">
        <template x-if="editingGroup">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
                <h3 id="edit-group-modal-title" class="text-base font-extrabold text-[#06233B]" x-text="'Edit Grup: ' + editingGroup.nama_grup"></h3>
                <form method="POST" :action="'/admin/telegram-groups/' + editingGroup.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_nama_grup" class="block text-xs font-bold uppercase text-[#06233B]">Nama Grup</label>
                        <input
                            id="edit_nama_grup"
                            type="text"
                            name="nama_grup"
                            x-model="editingGroup.nama_grup"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_chat_id" class="block text-xs font-bold uppercase text-[#06233B]">Chat ID</label>
                        <input
                            id="edit_chat_id"
                            type="text"
                            name="chat_id"
                            x-model="editingGroup.chat_id"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_thread_id" class="block text-xs font-bold uppercase text-[#06233B]">Thread ID (Opsional)</label>
                        <input
                            id="edit_thread_id"
                            type="number"
                            name="thread_id"
                            x-model="editingGroup.thread_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                        <label for="edit_is_all_team" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                            <input
                                id="edit_is_all_team"
                                type="checkbox"
                                name="is_all_team"
                                value="1"
                                x-model="editingGroup.is_all_team"
                                class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                            <span>Grup Utama ALL TEAM PSB</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingGroup = null"
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
