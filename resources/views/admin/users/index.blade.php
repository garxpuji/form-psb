@extends('layouts.admin', ['title' => 'Manajemen User & Teknisi'])

@section('content')
<div class="space-y-5" x-data="{ editingUser: null }">
    <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-lg font-extrabold text-[#06233B] sm:text-xl">Manajemen User & Penugasan Tim Telegram</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Kelola akun teknisi, umum, dan admin NOC. Jika <strong>Grup Telegram</strong> dikosongkan (<code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">null</code>), laporan otomatis dikirim ke <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">ALL TEAM PSB</code> dengan label <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">[NON-TIM PSB]</code>.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
        <!-- Create Form (4 Columns on Desktop) -->
        <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-5 shadow-sm xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah User / Teknisi Baru</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Tampilan Teknisi</label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: andri"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Username Login</label>
                        <input
                            type="text"
                            name="username"
                            required
                            placeholder="andri"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold lowercase text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Password</label>
                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="Min. 4 karakter"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Role</label>
                    <select
                        name="role"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                        <option value="teknisi">Teknisi PSB</option>
                        <option value="umum">Umum / Maintenance (Non-Tim)</option>
                        <option value="admin">Admin / NOC</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Penugasan Grup Telegram</label>
                    <select
                        name="telegram_group_id"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                        <option value="">-- Tanpa Tim (Kirim ke ALL TEAM PSB [NON-TIM PSB]) --</option>
                        @foreach ($telegramGroups as $group)
                        @if (! $group->is_all_team)
                        <option value="{{ $group->id }}">{{ $group->nama_grup }} ({{ $group->chat_id }})</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-3">
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm font-bold text-[#06233B]">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            checked
                            class="h-4 w-4 rounded border-[#5B82A6] text-[#005F99]">
                        <span>Akun Aktif (Dapat Login)</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78]">
                    Simpan User Baru
                </button>
            </form>
        </div>

        <!-- Users Table (8 Columns on Desktop) -->
        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-4 py-3.5">Nama & Username</th>
                            <th class="px-4 py-3.5">Role</th>
                            <th class="px-4 py-3.5">Grup Telegram</th>
                            <th class="px-4 py-3.5">Status Akun</th>
                            <th class="px-4 py-3.5">Total PSB</th>
                            <th class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#B8D4EC] bg-white">
                        @forelse ($users as $u)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3">
                                <div class="font-extrabold text-[#06233B]">{{ $u->name }}</div>
                                <div class="font-mono text-xs font-bold text-[#005F99]">@<span>{{ $u->username }}</span></div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->role === 'admin')
                                <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-mono text-xs font-bold text-[#075985]">ADMIN</span>
                                @elseif ($u->role === 'teknisi')
                                <span class="rounded border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono text-xs font-bold text-[#065F46]">TEKNISI</span>
                                @else
                                <span class="rounded border border-[#5B82A6] bg-[#EDF5FC] px-2 py-0.5 font-mono text-xs font-bold text-[#06233B]">UMUM</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($u->telegramGroup && ! $u->telegramGroup->is_all_team)
                                <span class="rounded border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono font-bold text-[#065F46]">
                                    {{ $u->telegramGroup->nama_grup }}
                                </span>
                                @else
                                <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono font-bold text-[#78350F]">
                                    ALL TEAM PSB [NON-TIM PSB]
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($u->is_active)
                                <span class="inline-flex rounded-md border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono text-xs font-bold text-[#065F46]">Aktif</span>
                                @else
                                <span class="inline-flex rounded-md border border-[#DC2626] bg-[#FEE2E2] px-2 py-0.5 font-mono text-xs font-bold text-[#991B1B]">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#06233B]">{{ $u->psb_orders_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingUser = @js([
                                                'id' => $u->id,
                                                'name' => $u->name,
                                                'username' => $u->username,
                                                'role' => $u->role,
                                                'telegram_group_id' => $u->telegram_group_id ?? '',
                                                'is_active' => $u->is_active,
                                            ])"
                                        class="inline-flex min-h-[40px] items-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-1.5 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    @if (auth()->id() !== $u->id)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.destroy', $u) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus user {{ $u->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-[40px] items-center rounded-lg border border-[#DC2626] bg-[#FEE2E2] px-3 py-1.5 font-bold text-[#991B1B] hover:bg-[#FECACA]">
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
                                Belum ada user terdaftar.
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
        x-show="editingUser !== null"
        x-cloak
        @keydown.escape.window="editingUser = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true">
        <template x-if="editingUser">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-6 shadow-2xl">
                <h3 class="text-base font-extrabold text-[#06233B]" x-text="'Edit User: ' + editingUser.name"></h3>
                <form method="POST" :action="'/admin/users/' + editingUser.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Tampilan</label>
                        <input
                            type="text"
                            name="name"
                            x-model="editingUser.name"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Username</label>
                        <input
                            type="text"
                            name="username"
                            x-model="editingUser.username"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold lowercase text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Password Baru (Kosongkan jika tetap)</label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Kosongkan jika tidak mengubah password"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Role</label>
                        <select
                            name="role"
                            x-model="editingUser.role"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                            <option value="teknisi">Teknisi PSB</option>
                            <option value="umum">Umum / Maintenance</option>
                            <option value="admin">Admin / NOC</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Penugasan Grup Telegram</label>
                        <select
                            name="telegram_group_id"
                            x-model="editingUser.telegram_group_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                            <option value="">-- Tanpa Tim (ALL TEAM PSB [NON-TIM PSB]) --</option>
                            @foreach ($telegramGroups as $group)
                            @if (! $group->is_all_team)
                            <option value="{{ $group->id }}">{{ $group->nama_grup }} ({{ $group->chat_id }})</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-3">
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm font-bold text-[#06233B]">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                x-model="editingUser.is_active"
                                class="h-4 w-4 rounded border-[#5B82A6] text-[#005F99]">
                            <span>Akun Aktif</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingUser = null"
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