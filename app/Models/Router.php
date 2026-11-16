<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_router',
        'ip_host',
        'api_port',
        'username',
        'password',
        'lan_interface',
        'is_pppoe_server',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_port' => 'integer',
            'password' => 'encrypted',
            'is_pppoe_server' => 'boolean',
        ];
    }

    /**
     * Sales codes routed to this distribution router.
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'ip_router_id');
    }

    /**
     * Simple Queue IP slots synchronized from this router.
     *
     * @return HasMany<RouterIpSlot, $this>
     */
    public function ipSlots(): HasMany
    {
        return $this->hasMany(RouterIpSlot::class, 'router_id');
    }
}
