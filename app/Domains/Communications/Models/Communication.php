<?php

namespace App\Domains\Communications\Models;

use App\Domains\Clients\Models\Client;
use App\Models\User;
use Database\Factories\CommunicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    use HasFactory;

    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_MEETING = 'meeting';

    protected $fillable = [
        'client_id',
        'type',
        'date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    protected static function newFactory(): CommunicationFactory
    {
        return CommunicationFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
