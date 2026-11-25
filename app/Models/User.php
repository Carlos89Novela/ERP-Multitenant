<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RuntimeException;

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
        'es_operador_principal',
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
            'es_operador_principal' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->getOriginal('es_operador_principal')) {
                // La cuenta principal es inmutable en su rol, privilegios y estado principal
                $user->es_operador_principal = true;
                $user->es_operador_plataforma = true;
                $user->role = 'operador_plataforma';
                $user->empresa_id = null;
            }
        });

        static::deleting(function (User $user): void {
            if ($user->es_operador_principal || $user->getOriginal('es_operador_principal')) {
                throw new RuntimeException('La cuenta principal del operador de plataforma es inmutable y no puede eliminarse.');
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function esOperadorPrincipal(): bool
    {
        return (bool) $this->es_operador_principal;
    }

    public function esOperadorPlataforma(): bool
    {
        return (bool) $this->es_operador_plataforma || (bool) $this->es_operador_principal;
    }

    public function esPropietario(): bool
    {
        return (bool) $this->es_propietario || $this->role === 'propietario';
    }
}
