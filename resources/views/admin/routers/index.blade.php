@extends('layouts.admin', ['title' => 'Master Data Router MikroTik'])

@section('content')
<div class="space-y-5" x-data="{ editingRouter: null }">
    <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-lg font-extrabold text-[#06233B] sm:text-xl">Master Data Router MikroTik</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Kelola router utama PPPoE (<code class="rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono font-bold text-[#075985]">is_pppoe_server = true</code>) dan 6 router distribusi Static IP per sales (<code class="rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono font-bold text-[#78350F]">is_pppoe_server = false</code>). Password disimpan terenkripsi.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
        <!-- Create Router Form (4 Columns on Desktop) -->
        <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-5 shadow-sm xl:col-span-4" x-data="{ isPppoe: false }">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah Router Baru</h2>
            <form method="POST" action="{{ route('admin.routers.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Router</label>
                    <input
                        type="text"
                        name="nama_router"
                        required
                        placeholder="Contoh: DIST-MIKROTIK-PAY"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase text-[#06233B]">IP / Host</label>
                        <input
                            type="text"
                            name="ip_host"
                            required
                            placeholder="10.10.1.1"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Port API</label>
                        <input
                            type="number"
                            name="api_port"
                            value="8728"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Username API</label>
                        <input
                            type="text"
                            name="username"
                            required
                            placeholder="api-psb"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Password API</label>
                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="Masukkan password"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                </div>

                <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-3">
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm font-bold text-[#06233B]">
                        <input
                            type="checkbox"
                            name="is_pppoe_server"
                            value="1"
                            x-model="isPppoe"
                            class="h-4 w-4 rounded border-[#5B82A6] text-[#005F99]">
                        <span>Jadikan Server PPPoE Utama</span>
                    </label>
                </div>

                <div x-show="!isPppoe">
                    <label class="block text-xs font-bold uppercase text-[#06233B]">LAN Interface (Static ARP)</label>
                    <input
                        type="text"
                        name="lan_interface"
                        value="bridge-local"
                        placeholder="Contoh: bridge-local"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78]">
                    Tambah Router
                </button>
            </form>
        </div>

        <!-- Routers Table (8 Columns on Desktop) -->
        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-4 py-3.5">Nama Router & Peran</th>
                            <th class="px-4 py-3.5">Host & Port API</th>
                            <th class="px-4 py-3.5">Kredensial & Interface</th>
                            <th class="px-4 py-3.5">Kapasitas Slot IP</th>
                            <th class="px-4 py-3.5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#B8D4EC] bg-white">
                        @forelse ($routers as $router)
                        <tr class="hover:bg-[#EDF5FC]/70">
                            <td class="px-4 py-3">
                                <div class="font-extrabold text-[#06233B]">{{ $router->nama_router }}</div>
                                <div class="mt-1">
                                    @if ($router->is_pppoe_server)
                                    <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-mono text-[11px] font-bold text-[#075985]">
                                        CENTRAL PPPoE SERVER
                                    </span>
                                    @else
                                    <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono text-[11px] font-bold text-[#78350F]">
                                        DISTRIBUSI STATIC IP
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-bold text-[#005F99]">
                                {{ $router->ip_host }}:{{ $router->api_port }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="font-semibold text-[#06233B]">User: <span class="font-mono">{{ $router->username }}</span></div>
                                <div class="text-[#2C5270]">Pass: <span class="font-mono">******** (Encrypted)</span></div>
                                @if (! $router->is_pppoe_server)
                                <div class="font-semibold text-[#06233B]">LAN: <span class="font-mono">{{ $router->lan_interface ?? '-' }}</span></div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($router->is_pppoe_server)
                                <span class="font-medium text-[#2C5270]">Khusus PPPoE Secret</span>
                                @else
                                <div class="font-mono font-bold text-[#065F46]">{{ $router->available_slots_count }} Kosong</div>
                                <div class="font-mono text-[#2C5270]">{{ $router->used_slots_count }} Terpakai</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="editingRouter = @js([
                                                'id' => $router->id,
                                                'nama_router' => $router->nama_router,
                                                'ip_host' => $router->ip_host,
                                                'api_port' => $router->api_port,
                                                'username' => $router->username,
                                                'lan_interface' => $router->lan_interface,
                                                'is_pppoe_server' => $router->is_pppoe_server,
                                            ])"
                                        class="inline-flex min-h-[40px] items-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-1.5 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                        Edit
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.routers.destroy', $router) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('Hapus router {{ $router->nama_router }}?');">
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
                                Belum ada router terdaftar. Gunakan formulir di sebelah kiri untuk menambahkan router MikroTik.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Router Modal (Masking Password) -->
    <div
        x-show="editingRouter !== null"
        x-cloak
        @keydown.escape.window="editingRouter = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true">
        <template x-if="editingRouter">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-6 shadow-2xl">
                <h3 class="text-base font-extrabold text-[#06233B]" x-text="'Edit Router: ' + editingRouter.nama_router"></h3>
                <form method="POST" :action="'/admin/routers/' + editingRouter.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Nama Router</label>
                        <input
                            type="text"
                            name="nama_router"
                            x-model="editingRouter.nama_router"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold uppercase text-[#06233B]">IP / Host</label>
                            <input
                                type="text"
                                name="ip_host"
                                x-model="editingRouter.ip_host"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-[#06233B]">Port API</label>
                            <input
                                type="number"
                                name="api_port"
                                x-model="editingRouter.api_port"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">Username API</label>
                        <input
                            type="text"
                            name="username"
                            x-model="editingRouter.username"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-[#06233B]">
                            Password API Baru (Kosongkan jika tidak diubah)
                        </label>
                        <input
                            type="password"
                            name="password"
                            placeholder="Kosongkan jika memakai password lama"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                    </div>
                    <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-3">
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm font-bold text-[#06233B]">
                            <input
                                type="checkbox"
                                name="is_pppoe_server"
                                value="1"
                                x-model="editingRouter.is_pppoe_server"
                                class="h-4 w-4 rounded border-[#5B82A6] text-[#005F99]">
                            <span>Server PPPoE Utama</span>
                        </label>
                    </div>
                    <div x-show="!editingRouter.is_pppoe_server">
                        <label class="block text-xs font-bold uppercase text-[#06233B]">LAN Interface</label>
                        <input
                            type="text"
                            name="lan_interface"
                            x-model="editingRouter.lan_interface"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingRouter = null"
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