<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'telegram_group_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'telegram_group_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine whether the user has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Assigned Telegram group for PSB report routing.
     *
     * @return BelongsTo<TelegramGroup, $this>
     */
    public function telegramGroup(): BelongsTo
    {
        return $this->belongsTo(TelegramGroup::class, 'telegram_group_id');
    }

    /**
     * PSB installation orders submitted by this user.
     *
     * @return HasMany<PsbOrder, $this>
     */
    public function psbOrders(): HasMany
    {
        return $this->hasMany(PsbOrder::class, 'user_id');
    }
}
