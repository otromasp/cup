<?php

namespace App\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable;

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_INACTIVO = 'inactivo';

    public const ROL_ADMINISTRADOR = 'administrador';

    public const ROL_COORDINADOR = 'coordinador';

    public const ROL_DOCENTE = 'docente';

    public const ROL_POSTULANTE = 'postulante';

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $authPasswordName = 'contrasena_hash';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'ci',
        'correo',
        'contrasena_hash',
        'estado',
        'rol',
        'credenciales_enviadas_en',
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'contrasena_hash',
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'id',
        'name',
        'email',
        'email_verified_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'estado' => self::ESTADO_ACTIVO,
        'rol' => self::ROL_POSTULANTE,
    ];

    /**
     * @return array<string, string>
     */
    public static function rolesGestionables(): array
    {
        return [
            self::ROL_ADMINISTRADOR => 'Administrador',
            self::ROL_COORDINADOR => 'Coordinador',
            self::ROL_DOCENTE => 'Docente',
            self::ROL_POSTULANTE => 'Postulante',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function estadosGestionables(): array
    {
        return [
            self::ESTADO_ACTIVO => 'Activo',
            self::ESTADO_INACTIVO => 'Inactivo',
        ];
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esCoordinador(): bool
    {
        return $this->rol === self::ROL_COORDINADOR;
    }

    public function puedeConfigurarGestionCup(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && ($this->esAdministrador() || $this->esCoordinador());
    }

    public function gestionesCup(): HasMany
    {
        return $this->hasMany(GestionCup::class, 'usuario_responsable_id', 'id_usuario');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'usuario_id', 'id_usuario');
    }

    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class, 'usuario_id', 'id_usuario');
    }

    public function tokensRecuperacion(): HasMany
    {
        return $this->hasMany(TokenRecuperacionContrasena::class, 'usuario_id', 'id_usuario');
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function getIdAttribute(): mixed
    {
        return $this->attributes['id_usuario'] ?? null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->attributes['correo'] ?? null;
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['correo'] = $value;
    }

    public function getEmailVerifiedAtAttribute(): mixed
    {
        return $this->attributes['correo_verificado_en'] ?? null;
    }

    public function setEmailVerifiedAtAttribute(mixed $value): void
    {
        $this->attributes['correo_verificado_en'] = $value;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->attributes['contrasena_hash'] ?? null;
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['contrasena_hash'] = $value;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'credenciales_enviadas_en' => 'datetime',
            'contrasena_hash' => 'hashed',
        ];
    }
}
