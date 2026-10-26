<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NormaRepository;
use App\Repositories\PublicacionRepository;
use App\Repositories\TipoNormaRepository;
use App\Validators\Validator;
use RuntimeException;
use Throwable;

final class NormaService
{
    private const ESTADOS_VALIDOS = ['BORRADOR', 'PUBLICADA', 'VIGENTE', 'DEROGADA', 'ARCHIVADA'];

    private NormaRepository $normas;
    private TipoNormaRepository $tipos;
    private PublicacionRepository $publicaciones;
    private AuditoriaService $auditoria;

    public function __construct(
        ?NormaRepository $normas = null,
        ?TipoNormaRepository $tipos = null,
        ?PublicacionRepository $publicaciones = null,
        ?AuditoriaService $auditoria = null
    ) {
        $this->normas = $normas ?? new NormaRepository();
        $this->tipos = $tipos ?? new TipoNormaRepository();
        $this->publicaciones = $publicaciones ?? new PublicacionRepository();
        $this->auditoria = $auditoria ?? new AuditoriaService();
    }

    public function validar(array $datos, ?int $idExcluido = null): Validator
    {
        $validador = Validator::make($datos)
            ->positiveInteger('tipo_norma_id', 'El tipo de norma')
            ->required('tipo_norma_id', 'El tipo de norma')
            ->required('titulo', 'El titulo')
            ->maxLength('titulo', 500, 'El titulo')
            ->maxLength('numero_norma', 100, 'El numero de norma')
            ->maxLength('siglas', 100, 'Las siglas')
            ->maxLength('entidad_emisora', 255, 'La entidad emisora')
            ->date('fecha_emision', 'La fecha de emision')
            ->date('fecha_publicacion', 'La fecha de publicacion')
            ->date('fecha_vigencia', 'La fecha de vigencia')
            ->date('fecha_derogatoria', 'La fecha de derogatoria')
            ->in('estado', self::ESTADOS_VALIDOS, 'El estado')
            ->required('estado', 'El estado');

        $tipoNormaId = (int) ($datos['tipo_norma_id'] ?? 0);
        if ($tipoNormaId > 0 && $this->tipos->buscarPorId($tipoNormaId) === null) {
            $validador->custom('tipo_norma_id', fn () => false, 'El tipo de norma seleccionado no existe.');
        }

        $numeroNorma = trim((string) ($datos['numero_norma'] ?? ''));
        if ($tipoNormaId > 0 && $numeroNorma !== '' && $this->normas->existeDuplicado($tipoNormaId, $numeroNorma, $idExcluido)) {
            $validador->custom(
                'numero_norma',
                fn () => false,
                'Ya existe una norma del mismo tipo con ese numero. Verifique que no sea un duplicado.'
            );
        }

        $fechaPublicacion = $datos['fecha_publicacion'] ?? null;
        $fechaEmision = $datos['fecha_emision'] ?? null;
        if (!empty($fechaPublicacion) && !empty($fechaEmision) && $fechaPublicacion < $fechaEmision) {
            $validador->custom(
                'fecha_publicacion',
                fn () => false,
                'La fecha de publicacion no puede ser anterior a la fecha de emision.'
            );
        }

        return $validador;
    }

    /**
     * Crea una norma y, opcionalmente, su primera publicacion oficial y
     * artículos iniciales en una sola transaccion.
     */
    public function crearConPublicacionYArticulos(
        array $datosNorma,
        int $usuarioId,
        ?array $datosPublicacion = null,
        array $articulosIniciales = []
    ): int {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $normaId = $this->normas->crear($datosNorma, $usuarioId);

            if ($datosPublicacion !== null) {
                $this->publicaciones->crear($normaId, $datosPublicacion);
            }

            foreach ($articulosIniciales as $orden => $articulo) {
                $articulo['orden'] = $articulo['orden'] ?? $orden;
                (new \App\Repositories\ArticuloRepository())->crear($normaId, $articulo, $usuarioId);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->auditoria->registrar('normas', $normaId, 'INSERT', ['titulo' => $datosNorma['titulo']]);

        return $normaId;
    }

    public function actualizar(int $id, array $datos): void
    {
        $this->normas->actualizar($id, $datos);
        $this->auditoria->registrar('normas', $id, 'UPDATE', ['titulo' => $datos['titulo']]);
    }

    public function marcarRevisada(int $id, int $usuarioRevisorId): void
    {
        $this->normas->marcarRevisada($id, $usuarioRevisorId);
        $this->auditoria->registrar('normas', $id, 'APROBACION', ['evento' => 'revision_vigencia']);
    }

    public function agregarPublicacion(int $normaId, array $datos): int
    {
        $validador = Validator::make($datos)
            ->maxLength('nombre_publicacion', 150, 'El nombre de la publicacion')
            ->maxLength('numero', 50, 'El numero')
            ->date('fecha_publicacion', 'La fecha de publicacion')
            ->url('url_fuente', 'La URL de la fuente');

        if ($validador->fails()) {
            throw new RuntimeException(implode(' ', $validador->firstErrorsFlat()));
        }

        $id = $this->publicaciones->crear($normaId, $datos);
        $this->auditoria->registrar('publicaciones_oficiales', $id, 'INSERT', ['norma_id' => $normaId]);

        return $id;
    }
}
