<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RelacionRepository;
use DateTimeImmutable;

/**
 * Determina el estado de vigencia que se MUESTRA al usuario. El campo
 * `normas.estado` es solo una clasificacion administrativa; este servicio
 * la combina con fechas y relaciones normativas antes de afirmar algo.
 *
 * Nunca se declara "vigente" sin que la norma haya sido revisada por un
 * REVISOR (ver `normas.revisado_por` / `fecha_revision`).
 */
final class VigenciaService
{
    private RelacionRepository $relaciones;

    public function __construct(?RelacionRepository $relaciones = null)
    {
        $this->relaciones = $relaciones ?? new RelacionRepository();
    }

    /**
     * @return array{etiqueta: string, clase_css: string, verificada: bool}
     */
    public function evaluar(array $norma): array
    {
        $hoy = new DateTimeImmutable('today');

        if ($norma['estado'] === 'DEROGADA') {
            return $this->resultado('Derogada', 'badge-derogada', true);
        }

        if (!empty($norma['fecha_derogatoria']) && new DateTimeImmutable($norma['fecha_derogatoria']) <= $hoy) {
            return $this->resultado('Derogada (por fecha de derogatoria)', 'badge-derogada', true);
        }

        if ($this->relaciones->existeDerogacionVigente((int) $norma['id'], $hoy)) {
            return $this->resultado(
                'Posiblemente derogada: existe una relacion normativa de derogacion/sustitucion. Verificar alcance.',
                'badge-pendiente',
                false
            );
        }

        if ($norma['estado'] === 'BORRADOR' || $norma['estado'] === 'ARCHIVADA') {
            return $this->resultado('No vigente (' . $norma['estado'] . ')', 'badge-borrador', true);
        }

        $tieneFechaVigencia = !empty($norma['fecha_vigencia'])
            && new DateTimeImmutable($norma['fecha_vigencia']) <= $hoy;
        $fueRevisada = !empty($norma['revisado_por']);

        if ($norma['estado'] === 'VIGENTE' && $tieneFechaVigencia && $fueRevisada) {
            return $this->resultado('Vigente (verificada)', 'badge-vigente', true);
        }

        return $this->resultado('Vigencia pendiente de verificacion', 'badge-pendiente', false);
    }

    private function resultado(string $etiqueta, string $claseCss, bool $verificada): array
    {
        return ['etiqueta' => $etiqueta, 'clase_css' => $claseCss, 'verificada' => $verificada];
    }
}
