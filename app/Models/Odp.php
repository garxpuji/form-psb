<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_odp',
        'kode_odp',
        'status_upgrade',
        'last_seq',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seq' => 'integer',
        ];
    }

    /**
     * Determine whether this ODP has been upgraded to PPPoE.
     */
    public function isUpgraded(): bool
    {
        return $this->status_upgrade === 'upgrade';
    }

    /**
     * PSB orders connected to this ODP.
     *
     * @return HasMany<PsbOrder, $this>
     */
    public function psbOrders(): HasMany
    {
        return $this->hasMany(PsbOrder::class, 'odp_id');
    }
}
