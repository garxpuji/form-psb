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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nama_pelanggan',
        'alamat',
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
