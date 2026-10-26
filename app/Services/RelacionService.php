<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RelacionRepository;
use App\Validators\Validator;

final class RelacionService
{
    private const TIPOS_VALIDOS = [
        'REFORMA', 'DEROGACION_TOTAL', 'DEROGACION_PARCIAL', 'SUSTITUCION', 'ACLARACION', 'OTRA',
    ];

    private RelacionRepository $relaciones;
    private AuditoriaService $auditoria;

    public function __construct(?RelacionRepository $relaciones = null, ?AuditoriaService $auditoria = null)
    {
        $this->relaciones = $relaciones ?? new RelacionRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function validar(array $datos): Validator
    {
        $validador = Validator::make($datos)
            ->required('norma_destino_id', 'La norma afectada')
            ->positiveInteger('norma_destino_id', 'La norma afectada')
            ->required('tipo_relacion', 'El tipo de relacion')
            ->in('tipo_relacion', self::TIPOS_VALIDOS, 'El tipo de relacion')
            ->date('fecha_efecto', 'La fecha de efecto');

        if ((int) ($datos['norma_destino_id'] ?? 0) === (int) ($datos['norma_origen_id'] ?? 0)) {
            $validador->custom('norma_destino_id', fn () => false, 'Una norma no puede relacionarse consigo misma.');
        }

        return $validador;
    }

    public function crear(array $datos): int
    {
        $id = $this->relaciones->crear($datos);
        $this->auditoria->registrar('relaciones_normativas', $id, 'INSERT', [
            'norma_origen_id' => $datos['norma_origen_id'],
            'norma_destino_id' => $datos['norma_destino_id'],
            'tipo_relacion' => $datos['tipo_relacion'],
        ]);

        return $id;
    }
}
