<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    use HasFactory;
    protected $table = 'niveles';

    protected $fillable = ['nombre', 'sigla'];

    public function instituciones()
    {
        return $this->hasMany(Institucion::class);
    }
}
