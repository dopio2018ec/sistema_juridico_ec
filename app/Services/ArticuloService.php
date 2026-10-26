<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ArticuloRepository;
use App\Validators\Validator;

final class ArticuloService
{
    private const TIPOS_VALIDOS = [
        'CONSIDERANDO', 'ARTICULO', 'DISPOSICION_GENERAL', 'DISPOSICION_TRANSITORIA',
        'DISPOSICION_REFORMATORIA', 'DISPOSICION_DEROGATORIA', 'DISPOSICION_FINAL', 'OTRO',
    ];

    private ArticuloRepository $articulos;
    private AuditoriaService $auditoria;

    public function __construct(?ArticuloRepository $articulos = null, ?AuditoriaService $auditoria = null)
    {
        $this->articulos = $articulos ?? new ArticuloRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function validar(array $datos, int $normaId, ?int $idExcluido = null): Validator
    {
        $validador = Validator::make($datos)
            ->required('articulo_numero', 'El numero de articulo')
            ->maxLength('articulo_numero', 50, 'El numero de articulo')
            ->maxLength('titulo', 500, 'El titulo')
            ->required('tipo_contenido', 'El tipo de contenido')
            ->in('tipo_contenido', self::TIPOS_VALIDOS, 'El tipo de contenido')
            ->required('contenido', 'El contenido');

        $numero = trim((string) ($datos['articulo_numero'] ?? ''));
        if ($numero !== '' && $this->articulos->existeNumeroEnNorma($normaId, $numero, $idExcluido)) {
            $validador->custom(
                'articulo_numero',
                fn () => false,
                'Ya existe un articulo/disposicion con ese numero en esta norma.'
            );
        }

        return $validador;
    }

    public function crear(int $normaId, array $datos, int $usuarioId): int
    {
        $id = $this->articulos->crear($normaId, $datos, $usuarioId);
        $this->auditoria->registrar('articulos', $id, 'INSERT', [
            'norma_id' => $normaId,
            'articulo_numero' => $datos['articulo_numero'],
        ]);

        return $id;
    }

    public function actualizar(int $id, array $datos, int $usuarioId): void
    {
        $this->articulos->actualizarContenido($id, $datos, $usuarioId);
        $this->auditoria->registrar('articulos', $id, 'UPDATE', [
            'evento' => 'texto_vigente_sustituido',
            'articulo_numero' => $datos['articulo_numero'],
        ]);
    }

    public function cambiarEstado(int $id, bool $activo, int $usuarioId): void
    {
        $this->articulos->cambiarEstado($id, $activo, $usuarioId);
        $this->auditoria->registrar('articulos', $id, 'UPDATE', [
            'evento' => $activo ? 'activacion' : 'desactivacion',
        ]);
    }
}
