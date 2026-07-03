<?php declare(strict_types=1);
namespace App\Enums;
enum RolUsuario: string {
    case Admin = 'admin';
    case Guest = 'guest';
}
