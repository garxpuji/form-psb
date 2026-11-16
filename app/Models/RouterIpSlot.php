<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterIpSlot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'router_id',
        'queue_mk_id',
        'ip_address',
        'queue_name',
        'status',
        'psb_order_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'router_id' => 'integer',
            'psb_order_id' => 'integer',
        ];
    }

    /**
     * Distribution router owning this IP slot.
     *
     * @return BelongsTo<Router, $this>
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    /**
     * PSB order that claimed this IP slot.
     *
     * @return BelongsTo<PsbOrder, $this>
     */
    public function psbOrder(): BelongsTo
    {
        return $this->belongsTo(PsbOrder::class, 'psb_order_id');
    }
}
