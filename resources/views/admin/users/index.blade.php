@extends('layouts.admin', ['title' => 'Manajemen User & Teknisi'])

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ editingUser: null, createRole: 'teknisi' }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Manajemen User & Penugasan Tim Telegram</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Atur akun teknisi, umum, dan admin NOC. Apabila kolom Grup Telegram dikosongkan, aplikasi mengirim laporan instalasi user tersebut ke grup <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">ALL TEAM PSB</code> dengan tanda <code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">[NON-TIM PSB]</code>.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5 xl:col-span-4">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah User / Teknisi Baru</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="user_name" class="block text-xs font-bold uppercase text-[#06233B]">Nama Tampilan Teknisi</label>
                    <input
                        id="user_name"
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: andri"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="user_username" class="block text-xs font-bold uppercase text-[#06233B]">Username Login</label>
                        <input
                            id="user_username"
                            type="text"
                            name="username"
                            required
                            placeholder="andri"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold lowercase text-[#06233B]">
                    </div>
                    <div>
                        <label for="user_password" class="block text-xs font-bold uppercase text-[#06233B]">Password</label>
                        <input
                            id="user_password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            :minlength="createRole === 'admin' ? 12 : 8"
                            :placeholder="createRole === 'admin' ? 'Min. 12 krktr' : 'Min. 8 krktr'"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                </div>

                <div>
                    <label for="user_role" class="block text-xs font-bold uppercase text-[#06233B]">Role</label>
                    <select
                        id="user_role"
                        name="role"
                        x-model="createRole"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                        <option value="teknisi">Teknisi PSB (Min. 8 karakter)</option>
                        <option value="umum">Umum / Maintenance (Min. 8 karakter)</option>
                        <option value="admin">Admin / NOC (Min. 12 karakter)</option>
                    </select>
                </div>

                <div>
                    <label for="user_telegram_group_id" class="block text-xs font-bold uppercase text-[#06233B]">Penugasan Grup Telegram</label>
                    <select
                        id="user_telegram_group_id"
                        name="telegram_group_id"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                        <option value="">-- Tanpa Tim (ALL TEAM PSB) --</option>
                        @foreach ($telegramGroups as $group)
                        @if (! $group->is_all_team)
                        <option value="{{ $group->id }}">{{ $group->nama_grup }} ({{ $group->chat_id }})</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                    <label for="create_user_is_active" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                        <input
                            id="create_user_is_active"
                            type="checkbox"
                            name="is_active"
                            value="1"
                            checked
                            class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                        <span>Akun Aktif (Dapat Login)</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                    Simpan User Baru
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Nama & Username</th>
                            <th scope="col" class="px-4 py-3.5">Role</th>
                            <th scope="col" class="px-4 py-3.5">Grup Telegram</th>
                            <th scope="col" class="px-4 py-3.5">Status Akun</th>
                            <th scope="col" class="px-4 py-3.5">Total PSB</th>
                            <th scope="col" class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7FA6C9] bg-white">
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
                                <span class="rounded border border-[#4A7396] bg-[#EDF5FC] px-2 py-0.5 font-mono text-xs font-bold text-[#06233B]">UMUM</span>
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
                                        class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
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
                                Belum ada user terdaftar. Tambahkan akun baru menggunakan formulir di sebelah kiri.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="editingUser !== null"
        x-cloak
        x-trap.inert.noscroll="editingUser !== null"
        @keydown.escape.window="editingUser = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-user-modal-title">
        <template x-if="editingUser">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
                <h3 id="edit-user-modal-title" class="text-base font-extrabold text-[#06233B]" x-text="'Edit User: ' + editingUser.name"></h3>
                <form method="POST" :action="'/admin/users/' + editingUser.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_user_name" class="block text-xs font-bold uppercase text-[#06233B]">Nama Tampilan</label>
                        <input
                            id="edit_user_name"
                            type="text"
                            name="name"
                            x-model="editingUser.name"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_user_username" class="block text-xs font-bold uppercase text-[#06233B]">Username</label>
                        <input
                            id="edit_user_username"
                            type="text"
                            name="username"
                            x-model="editingUser.username"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold lowercase text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_user_password" class="block text-xs font-bold uppercase text-[#06233B]" x-text="editingUser.role === 'admin' ? 'Password Baru (Min. 12 karakter, kosongkan jika tetap)' : 'Password Baru (Min. 8 karakter, kosongkan jika tetap)'">
                            Password Baru
                        </label>
                        <input
                            id="edit_user_password"
                            type="password"
                            name="password"
                            placeholder="Biarkan kosong jika tidak mengubah password"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_user_role" class="block text-xs font-bold uppercase text-[#06233B]">Role</label>
                        <select
                            id="edit_user_role"
                            name="role"
                            x-model="editingUser.role"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            <option value="teknisi">Teknisi PSB</option>
                            <option value="umum">Umum / Maintenance</option>
                            <option value="admin">Admin / NOC</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_user_telegram_group" class="block text-xs font-bold uppercase text-[#06233B]">Penugasan Grup Telegram</label>
                        <select
                            id="edit_user_telegram_group"
                            name="telegram_group_id"
                            x-model="editingUser.telegram_group_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            <option value="">-- Tanpa Tim (ALL TEAM PSB [NON-TIM PSB]) --</option>
                            @foreach ($telegramGroups as $group)
                            @if (! $group->is_all_team)
                            <option value="{{ $group->id }}">{{ $group->nama_grup }} ({{ $group->chat_id }})</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                        <label for="edit_user_is_active" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                            <input
                                id="edit_user_is_active"
                                type="checkbox"
                                name="is_active"
                                value="1"
                                x-model="editingUser.is_active"
                                class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                            <span>Akun Aktif</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingUser = null"
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
