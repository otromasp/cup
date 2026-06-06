<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionUsuario extends Model
{
    protected $table = 'sesiones_usuario';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }
}
