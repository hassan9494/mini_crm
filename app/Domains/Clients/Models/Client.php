<?php

namespace App\Domains\Clients\Models;

use App\Domains\Communications\Models\Communication;
use App\Domains\FollowUps\Models\FollowUp;
use App\Models\User;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    public const STATUS_HOT = 'hot';
    public const STATUS_WARM = 'warm';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'status',
        'assigned_to',
        'last_communication_date',
    ];

    protected $casts = [
        'last_communication_date' => 'datetime',
    ];

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    public function assignedRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
