<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PsbOrder extends Model
{
    use HasFactory;

    /**
     * Mandatory installation photo fields and human-readable labels.
     *
     * @var array<string, string>
     */
    public const PHOTO_FIELDS = [
        'foto_mac_ont' => '1. Foto MAC ONT',
        'foto_odp' => '2. Foto ODP',
        'foto_kertas_registrasi' => '3. Foto Kertas Registrasi',
        'foto_ktp_pelanggan' => '4. Foto KTP Pelanggan',
        'foto_rumah_pelanggan' => '5. Foto Rumah Pelanggan',
    ];

    /**
     * Trusted Google Maps domains allowed for direct Shareloc linking.
     *
     * @var list<string>
     */
    private const TRUSTED_MAP_HOSTS = [
        'google.com',
        'www.google.com',
        'maps.google.com',
        'maps.app.goo.gl',
        'goo.gl',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nama_pelanggan',
        'alamat',
        'shareloc',
        'no_tlp',
        'odp_id',
        'port',
        'paket_id',
        'mac_address',
        'brand_router',
        'jarak_kabel',
        'signal_odp',
        'signal_ont',
        'sales_id',
        'nama_teknisi',
        'foto_mac_ont',
        'foto_odp',
        'foto_kertas_registrasi',
        'foto_ktp_pelanggan',
        'foto_rumah_pelanggan',
        'tipe_koneksi',
        'hasil_poe_ip',
        'status_sync',
        'error_log',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'odp_id' => 'integer',
            'port' => 'integer',
            'paket_id' => 'integer',
            'sales_id' => 'integer',
        ];
    }

    /**
     * Return a safe Google Maps URL without exposing NOC operators to arbitrary external domains.
     */
    public function safeSharelocUrl(): string
    {
        $raw = trim((string) $this->shareloc);
        if ($raw === '') {
            return 'https://www.google.com/maps';
        }

        if (preg_match('/^https?:\/\//i', $raw) === 1) {
            $parsed = parse_url($raw);
            $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
            $host = strtolower((string) ($parsed['host'] ?? ''));

            if (in_array($scheme, ['http', 'https'], true) && in_array($host, self::TRUSTED_MAP_HOSTS, true)) {
                return $raw;
            }
        }

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($raw);
    }

    /**
     * Build the structured payload for the NOC Split Photo Verification Lightbox.
     *
     * @return array<string, mixed>
     */
    public function adminPhotoModalPayload(): array
    {
        $photos = [];
        foreach (self::PHOTO_FIELDS as $field => $label) {
            $photos[] = [
                'field' => $field,
                'label' => $label,
                'url' => route('admin.orders.photo', ['order' => $this->id, 'field' => $field]),
            ];
        }

        return [
            'order_id' => $this->id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'no_tlp' => $this->no_tlp,
            'alamat' => $this->alamat,
            'odp' => ($this->odp?->nama_odp ?? '-').' / Port '.$this->port,
            'paket' => $this->paket?->nama_paket ?? '-',
            'mac_address' => $this->mac_address,
            'brand_router' => $this->brand_router,
            'jarak_kabel' => $this->jarak_kabel,
            'signal_odp' => $this->signal_odp,
            'signal_ont' => $this->signal_ont,
            'hasil_poe_ip' => $this->hasil_poe_ip ?? 'GAGAL AUTO-CONFIG',
            'nama_teknisi' => $this->nama_teknisi,
            'photos' => $photos,
        ];
    }

    /**
     * Technician or user who submitted this PSB order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Target ODP for this installation.
     *
     * @return BelongsTo<Odp, $this>
     */
    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class, 'odp_id');
    }

    /**
     * Subscribed bandwidth package.
     *
     * @return BelongsTo<Paket, $this>
     */
    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class, 'paket_id');
    }

    /**
     * Sales channel associated with this customer.
     *
     * @return BelongsTo<Sale, $this>
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    /**
     * Claimed Simple Queue IP slot (when connection type is static_ip).
     *
     * @return HasOne<RouterIpSlot, $this>
     */
    public function ipSlot(): HasOne
    {
        return $this->hasOne(RouterIpSlot::class, 'psb_order_id');
    }
}
