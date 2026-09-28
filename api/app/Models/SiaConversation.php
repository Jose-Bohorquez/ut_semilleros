<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiaConversation extends Model
{
    protected $fillable = [
        'token', 'user_id', 'ip_hash', 'page', 'status', 'message_count',
        'rating', 'rating_skipped', 'feedback', 'admin_note', 'reviewed_by', 'reviewed_at', 'closed_at',
    ];

    protected $casts = [
        'rating_skipped' => 'boolean',
        'reviewed_at'    => 'datetime',
        'closed_at'      => 'datetime',
    ];

    protected $hidden = ['token', 'ip_hash'];

    public function messages()
    {
        return $this->hasMany(SiaMessage::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
