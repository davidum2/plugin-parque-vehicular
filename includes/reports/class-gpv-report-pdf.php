<?php

/**
 * Clase para generar reportes PDF de movimientos
 *
 * @package GPV
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Verificar que TCPDF está disponible
require_once GPV_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';

/**
 * Clase extendida de TCPDF para reportes de movimientos
 */
class GPV_Report_PDF extends TCPDF
{
    // Variables para el encabezado
    protected $reporte_numero;
    protected $reporte_fecha;
    protected $reporte_firmante;

    /**
     * Constructor
     */
    public function __construct($reporte_numero = '', $reporte_fecha = '', $reporte_firmante = '')
    {
        parent::__construct('P', 'mm', 'LETTER', true, 'UTF-8', false);

        // Inicializar las variables del reporte
        $this->reporte_numero = $reporte_numero;
        $this->reporte_fecha = $reporte_fecha;
        $this->reporte_firmante = $reporte_firmante;

        // Configurar documento
        $this->SetCreator('Gestión de Parque Vehicular v' . GPV_VERSION);
        $this->SetAuthor(get_bloginfo('name'));
        $this->SetTitle('Reporte de Movimientos - ' . $reporte_numero);
        $this->SetSubject('Reporte de Movimientos Vehiculares');

        // Configurar márgenes
        $this->SetMargins(20, 45, 20);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);

        // Configurar auto page breaks
        $this->SetAutoPageBreak(TRUE, 25);

        // Establecer fuente predeterminada
        $this->SetFont('helvetica', '', 10);
    }

    /**
     * Personalizar encabezado de página
     */
    public function Header()
    {
        // Logo (si existe)
        global $GPV_Database;
        $logo_url = $GPV_Database->get_setting('logo_url');
        if ($logo_url) {
            $this->Image($logo_url, 15, 10, 40, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // Título y número de reporte
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, 'REPORTE DE MOVIMIENTOS', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 8, 'Número: ' . $this->reporte_numero, 0, 1, 'C');
        $this->Cell(0, 8, 'Fecha: ' . $this->reporte_fecha, 0, 1, 'C');

        // Línea horizontal
        $this->Line(15, 37, 195, 37);
    }

    /**
     * Personalizar pie de página
     */
    public function Footer()
    {
        // Posición a 15 mm del borde inferior
        $this->SetY(-15);

        // Fuente
        $this->SetFont('helvetica', 'I', 8);

        // Número de página
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
    }
}

/**
 * Clase principal para generar el reporte de movimientos
 */
class GPV_Report_Generator
{
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $GPV_Database;
        $this->database = $GPV_Database;
    }

    /**
     * Generar reporte PDF para un reporte específico
     *
     * @param int $reporte_id ID del reporte
     * @return string|bool Ruta al archivo PDF generado o false en caso de error
     */
    public function generate_report($reporte_id)
    {
        // Obtener datos del reporte
        $reporte = $this->database->get_reporte($reporte_id);

        if (!$reporte) {
            return false;
        }

        // Obtener datos del firmante
        $firmante = null;
        if ($reporte->firmante_id) {
            $firmantes = $this->database->get_firmantes(['id' => $reporte->firmante_id]);
            if (!empty($firmantes)) {
                $firmante = $firmantes[0];
            }
        }

        // Formatear fecha
        $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reporte->fecha_reporte));

        // Crear el reporte PDF
        $pdf = new GPV_Report_PDF($reporte->numero_mensaje, $fecha_formateada, $firmante ? $firmante->nombre : '');

        // Añadir página
        $pdf->AddPage();

        // Información del reporte
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Información General', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Cell(60, 8, 'Vehículo:', 0, 0, 'L');
        $pdf->Cell(0, 8, $reporte->vehiculo_siglas . ' - ' . $reporte->vehiculo_nombre, 0, 1, 'L');

        $pdf->Cell(60, 8, 'Conductor:', 0, 0, 'L');
        $pdf->Cell(0, 8, $reporte->conductor, 0, 1, 'L');

        $pdf->Cell(60, 8, 'Fecha de inicio:', 0, 0, 'L');
        $pdf->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_inicial)) . ' ' . date('H:i', strtotime($reporte->hora_inicial)), 0, 1, 'L');

        $pdf->Cell(60, 8, 'Fecha de finalización:', 0, 0, 'L');
        $pdf->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_final)) . ' ' . date('H:i', strtotime($reporte->hora_final)), 0, 1, 'L');

        $pdf->Cell(60, 8, 'Odómetro inicial:', 0, 0, 'L');
        $pdf->Cell(0, 8, number_format($reporte->odometro_inicial, 2) . ' km', 0, 1, 'L');

        $pdf->Cell(60, 8, 'Odómetro final:', 0, 0, 'L');
        $pdf->Cell(0, 8, number_format($reporte->odometro_final, 2) . ' km', 0, 1, 'L');

        $pdf->Cell(60, 8, 'Distancia total:', 0, 0, 'L');
        $pdf->Cell(0, 8, number_format($reporte->distancia_total, 2) . ' km', 0, 1, 'L');

        // Detalles de movimientos
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Detalle de Movimientos', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        // Obtener movimientos individuales
        $movimientos_ids = explode(',', $reporte->movimientos_incluidos);

        // Tabla de movimientos
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(20, 7, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'Hora', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Odóm. Salida', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Odóm. Entrada', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Distancia', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Propósito', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 8);

        $total_distancia = 0;

        foreach ($movimientos_ids as $movimiento_id) {
            $movimiento = $this->database->get_movement($movimiento_id);
            if ($movimiento) {
                $fecha = date_i18n(get_option('date_format'), strtotime($movimiento->hora_salida));
                $hora = date('H:i', strtotime($movimiento->hora_salida));
                $distancia = $movimiento->distancia_recorrida;
                $total_distancia += $distancia;

                $pdf->Cell(20, 6, $fecha, 1, 0, 'C');
                $pdf->Cell(15, 6, $hora, 1, 0, 'C');
                $pdf->Cell(30, 6, number_format($movimiento->odometro_salida, 2) . ' km', 1, 0, 'C');
                $pdf->Cell(30, 6, number_format($movimiento->odometro_entrada, 2) . ' km', 1, 0, 'C');
                $pdf->Cell(30, 6, number_format($distancia, 2) . ' km', 1, 0, 'C');
                $pdf->Cell(50, 6, $movimiento->proposito ?: 'N/A', 1, 1, 'C');
            }
        }

        // Total de distancia
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(95, 7, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell(30, 7, number_format($total_distancia, 2) . ' km', 1, 0, 'C', true);
        $pdf->Cell(50, 7, '', 1, 1, 'C', true);

        // Sección de firmas
        $pdf->Ln(20);

        // Firma del conductor
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 8, '____________________________', 0, 0, 'C');
        $pdf->Cell(15, 8, '', 0, 0, 'C');
        $pdf->Cell(80, 8, '____________________________', 0, 1, 'C');

        $pdf->Cell(80, 8, $reporte->conductor, 0, 0, 'C');
        $pdf->Cell(15, 8, '', 0, 0, 'C');
        $pdf->Cell(80, 8, $firmante ? $firmante->nombre : 'Firma Autorizada', 0, 1, 'C');

        $pdf->Cell(80, 8, 'Conductor', 0, 0, 'C');
        $pdf->Cell(15, 8, '', 0, 0, 'C');
        $pdf->Cell(80, 8, $firmante ? $firmante->cargo : '', 0, 1, 'C');

        // Crear directorio para PDFs si no existe
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/gpv-reportes';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        // Nombre del archivo
        $filename = 'reporte-' . $reporte_id . '.pdf';
        $filepath = $pdf_dir . '/' . $filename;

        // Guardar PDF
        $pdf->Output($filepath, 'F');

        // Actualizar estado del reporte
        $this->database->update_reporte_estado($reporte_id, 'generado', $filename);

        return $filepath;
    }
}
