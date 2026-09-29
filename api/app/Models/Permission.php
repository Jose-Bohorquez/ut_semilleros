<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['module', 'action', 'label'];

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function key(): string
    {
        return "{$this->module}.{$this->action}";
    }
}
