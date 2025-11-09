<?php

namespace App\Domains\FollowUps\Models;

use App\Domains\Clients\Models\Client;
use App\Models\User;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'client_id',
        'user_id',
        'due_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    protected static function newFactory(): FollowUpFactory
    {
        return FollowUpFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
