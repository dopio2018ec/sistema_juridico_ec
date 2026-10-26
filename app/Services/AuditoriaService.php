<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\AuditoriaRepository;

final class AuditoriaService
{
    private AuditoriaRepository $repositorio;

    public function __construct(?AuditoriaRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?? new AuditoriaRepository();
    }

    public function registrar(string $tabla, int $registroId, string $accion, array $detalle = []): void
    {
        $this->repositorio->registrar(Auth::id(), $tabla, $registroId, $accion, $detalle);
    }
}
