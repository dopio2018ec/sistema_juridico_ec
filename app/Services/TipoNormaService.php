<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TipoNormaRepository;
use App\Validators\Validator;
use RuntimeException;

final class TipoNormaService
{
    private TipoNormaRepository $repositorio;
    private AuditoriaService $auditoria;

    public function __construct(?TipoNormaRepository $repositorio = null, ?AuditoriaService $auditoria = null)
    {
        $this->repositorio = $repositorio ?? new TipoNormaRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function validar(array $datos, ?int $idExcluido = null): Validator
    {
        $validador = Validator::make($datos)
            ->required('codigo', 'El codigo')
            ->maxLength('codigo', 30, 'El codigo')
            ->required('nombre', 'El nombre')
            ->maxLength('nombre', 100, 'El nombre')
            ->maxLength('descripcion', 255, 'La descripcion');

        $codigo = strtoupper(trim((string) ($datos['codigo'] ?? '')));
        if ($codigo !== '' && $this->repositorio->codigoExiste($codigo, $idExcluido)) {
            $validador->custom('codigo', fn () => false, 'Ya existe un tipo de norma con ese codigo.');
        }

        return $validador;
    }

    public function crear(string $codigo, string $nombre, ?string $descripcion): int
    {
        $id = $this->repositorio->crear(strtoupper(trim($codigo)), $nombre, $descripcion);
        $this->auditoria->registrar('tipos_norma', $id, 'INSERT', ['codigo' => $codigo, 'nombre' => $nombre]);

        return $id;
    }

    public function actualizar(int $id, string $nombre, ?string $descripcion): void
    {
        $this->repositorio->actualizar($id, $nombre, $descripcion);
        $this->auditoria->registrar('tipos_norma', $id, 'UPDATE', ['nombre' => $nombre]);
    }

    public function cambiarEstado(int $id, bool $activo): void
    {
        if (!$activo && $this->repositorio->tieneNormasAsociadas($id)) {
            throw new RuntimeException('No se puede desactivar: el tipo de norma tiene normas asociadas.');
        }

        $this->repositorio->cambiarEstado($id, $activo);
        $this->auditoria->registrar('tipos_norma', $id, 'UPDATE', [
            'evento' => $activo ? 'activacion' : 'desactivacion',
        ]);
    }
}
