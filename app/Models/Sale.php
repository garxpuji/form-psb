<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_sales',
        'kode_sales_ip',
        'kode_sales_pppoe',
        'ip_router_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ip_router_id' => 'integer',
        ];
    }

    /**
     * Distribution MikroTik router for Static IP installations under this sales code.
     *
     * @return BelongsTo<Router, $this>
     */
    public function ipRouter(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'ip_router_id');
    }

    /**
     * PSB orders associated with this sales record.
     *
     * @return HasMany<PsbOrder, $this>
     */
    public function psbOrders(): HasMany
    {
        return $this->hasMany(PsbOrder::class, 'sales_id');
    }
}
