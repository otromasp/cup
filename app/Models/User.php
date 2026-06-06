<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Usuario
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
}
