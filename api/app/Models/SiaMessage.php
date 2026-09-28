<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiaMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content', 'status',
        'prompt_tokens', 'completion_tokens', 'latency_ms', 'model',
    ];

    public function conversation()
    {
        return $this->belongsTo(SiaConversation::class, 'conversation_id');
    }
}
