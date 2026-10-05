<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistroCamposBuscables;
use Illuminate\Http\JsonResponse;

/**
 * Descubrimiento de los campos buscables del listado de proyecciones.
 *
 * Alimenta el picker "Buscar en ▾" del frontend para que la lista de campos viva
 * en UN solo lugar (el registro del backend) y no se duplique en el cliente.
 */
final class ProyeccionBusquedaController extends Controller
{
    public function __construct(private readonly RegistroCamposBuscables $registro) {}

    /**
     * No se cachea: el payload se deriva de una constante de clase de 23 entradas.
     * Cachear en servidor metería un problema de invalidación a cambio de nada.
     * Lo que sí se cachea es en el cliente (una request en `ngOnInit`).
     */
    public function camposBuscables(): JsonResponse
    {
        return response()->json([
            'data' => $this->registro->opciones(),
        ]);
    }
}
