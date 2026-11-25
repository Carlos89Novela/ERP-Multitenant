<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'empresa_id',
        'role',
        'es_propietario',
        'puede_autogestionar_template',
        'es_operador_plataforma',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_propietario' => 'boolean',
            'puede_autogestionar_template' => 'boolean',
            'es_operador_plataforma' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function esOperadorPlataforma(): bool
    {
        return (bool) $this->es_operador_plataforma;
    }

    public function esPropietario(): bool
    {
        return (bool) $this->es_propietario || $this->role === 'propietario';
    }
}
