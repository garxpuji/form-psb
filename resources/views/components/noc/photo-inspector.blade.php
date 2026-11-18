{{-- Accessible Split-View Photo & Customer Verification Lightbox for NOC Operators --}}
<div
    x-show="activePhotoModal !== null"
    x-cloak
    x-trap.inert.noscroll="activePhotoModal !== null"
    @keydown.escape.window="activePhotoModal = null"
    @keydown.arrow-right.window="if (activePhotoModal) { activePhotoIndex = (activePhotoIndex + 1) % activePhotoModal.photos.length; photoRotation = 0; }"
    @keydown.arrow-left.window="if (activePhotoModal) { activePhotoIndex = (activePhotoIndex - 1 + activePhotoModal.photos.length) % activePhotoModal.photos.length; photoRotation = 0; }"
    class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/85 p-3 sm:p-5"
    role="dialog"
    aria-modal="true"
    aria-labelledby="noc-photo-inspector-title">
    <template x-if="activePhotoModal">
        <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border-2 border-[#005F99] bg-white shadow-2xl">
            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#7FA6C9] bg-[#072A47] px-4 py-3 text-white sm:px-6">
                <div class="min-w-0">
                    <h3 id="noc-photo-inspector-title" class="truncate text-sm font-extrabold sm:text-base" x-text="'Verifikasi 5 Foto PSB: ' + activePhotoModal.nama_pelanggan"></h3>
                    <p class="truncate text-xs text-[#E0F2FE]" x-text="'Order #' + activePhotoModal.order_id + ' · ODP ' + activePhotoModal.odp + ' · Teknisi: ' + activePhotoModal.nama_teknisi"></p>
                </div>
                <button
                    type="button"
                    @click="activePhotoModal = null"
                    class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-lg border border-[#7DD3FC] bg-[#0B3B63] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#991B1B]">
                    <span>Tutup (Esc)</span>
                </button>
            </div>

            {{-- Split Content --}}
            <div class="grid flex-1 grid-cols-1 overflow-y-auto lg:grid-cols-12">
                {{-- Left/Main Stage: Full Uncropped Photo + Controls --}}
                <div class="flex flex-col justify-between bg-[#051F36] p-3.5 text-white sm:p-5 lg:col-span-8">
                    {{-- Photo Category Selector Pills --}}
                    <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Pilih Foto Dokumentasi PSB">
                        <template x-for="(photo, idx) in activePhotoModal.photos" :key="photo.field">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="activePhotoIndex === idx ? 'true' : 'false'"
                                @click="activePhotoIndex = idx; photoRotation = 0;"
                                class="min-h-[40px] rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
                                :class="activePhotoIndex === idx ? 'border-[#7DD3FC] bg-[#005F99] text-white' : 'border-[#124670] bg-[#0B3B63] text-[#E0F2FE] hover:bg-[#124670]'"
                                x-text="photo.label"></button>
                        </template>
                    </div>

                    {{-- Active Image Canvas --}}
                    <div class="relative my-3 flex min-h-[280px] flex-1 items-center justify-center overflow-hidden rounded-xl border border-[#124670] bg-[#031525] p-2 sm:min-h-[380px]">
                        <img
                            :src="activePhotoModal.photos[activePhotoIndex].url"
                            :alt="activePhotoModal.photos[activePhotoIndex].label + ' - ' + activePhotoModal.nama_pelanggan"
                            class="max-h-[52vh] max-w-full object-contain transition-transform duration-200"
                            :style="`transform: rotate(${photoRotation}deg)`">
                    </div>

                    {{-- Navigation & Rotate Toolbar --}}
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="activePhotoIndex = (activePhotoIndex - 1 + activePhotoModal.photos.length) % activePhotoModal.photos.length; photoRotation = 0;"
                                class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC]/70 bg-[#0B3B63] px-3 py-2 text-xs font-bold text-white hover:bg-[#005F99]">
                                &larr; Sebelumnya
                            </button>
                            <button
                                type="button"
                                @click="activePhotoIndex = (activePhotoIndex + 1) % activePhotoModal.photos.length; photoRotation = 0;"
                                class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC]/70 bg-[#0B3B63] px-3 py-2 text-xs font-bold text-white hover:bg-[#005F99]">
                                Berikutnya &rarr;
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="photoRotation = (photoRotation + 90) % 360"
                                class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#7DD3FC]/70 bg-[#0B3B63] px-3 py-2 text-xs font-bold text-white hover:bg-[#005F99]">
                                <span>Putar 90&deg;</span>
                            </button>
                            <a
                                :href="activePhotoModal.photos[activePhotoIndex].url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-[44px] items-center rounded-lg bg-[#005F99] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
                                Buka Ukuran Penuh
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Right Stage: Side-by-Side Order Metadata Checklist --}}
                <div class="border-t border-[#7FA6C9] bg-[#EDF5FC] p-4 sm:p-5 lg:col-span-4 lg:border-l lg:border-t-0">
                    <div class="text-xs font-extrabold uppercase tracking-wider text-[#06233B]">
                        Data Pembanding Verifikasi NOC
                    </div>
                    <p class="mt-0.5 text-xs text-[#2C5270]">
                        Cocokkan foto KTP, label MAC ONT, dan port ODP langsung dengan data di bawah ini.
                    </p>

                    <dl class="mt-3.5 space-y-2.5 text-xs">
                        <div class="rounded-xl border border-[#7FA6C9] bg-white p-3">
                            <dt class="font-bold uppercase text-[#2C5270]">Pelanggan & Kontak</dt>
                            <dd class="mt-0.5 text-sm font-extrabold text-[#06233B]" x-text="activePhotoModal.nama_pelanggan"></dd>
                            <dd class="font-mono font-bold text-[#005F99]" x-text="activePhotoModal.no_tlp"></dd>
                            <dd class="mt-1 text-[#2C5270]" x-text="activePhotoModal.alamat"></dd>
                        </div>

                        <div class="rounded-xl border border-[#7FA6C9] bg-white p-3">
                            <dt class="font-bold uppercase text-[#2C5270]">MAC Address & Perangkat ONT</dt>
                            <dd class="mt-0.5 font-mono text-sm font-extrabold text-[#06233B]" x-text="activePhotoModal.mac_address"></dd>
                            <dd class="mt-0.5 text-[#2C5270]" x-text="activePhotoModal.brand_router + ' · Kabel: ' + activePhotoModal.jarak_kabel"></dd>
                        </div>

                        <div class="rounded-xl border border-[#7FA6C9] bg-white p-3">
                            <dt class="font-bold uppercase text-[#2C5270]">ODP, Paket & Redaman Optik</dt>
                            <dd class="mt-0.5 font-bold text-[#06233B]" x-text="activePhotoModal.odp + ' · Paket ' + activePhotoModal.paket"></dd>
                            <dd class="mt-1 font-mono font-bold text-[#06233B]" x-text="'ODP: ' + activePhotoModal.signal_odp + ' | ONT: ' + activePhotoModal.signal_ont"></dd>
                        </div>

                        <div class="rounded-xl border border-[#7FA6C9] bg-white p-3">
                            <dt class="font-bold uppercase text-[#2C5270]">Hasil Provisioning POE / IP</dt>
                            <dd class="mt-0.5 break-all font-mono font-extrabold text-[#065F46]" x-text="activePhotoModal.hasil_poe_ip"></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </template>
</div>
