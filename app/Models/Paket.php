<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_paket',
        'queue_max_limit',
        'pppoe_profile',
    ];

    /**
     * PSB orders subscribed to this bandwidth package.
     *
     * @return HasMany<PsbOrder, $this>
     */
    public function psbOrders(): HasMany
    {
        return $this->hasMany(PsbOrder::class, 'paket_id');
    }
}
