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
    protected $numero_mensaje;
    protected $fecha_reporte;
    protected $nombre_firmante;
    private $database;
    /**
     * Constructor
     */
    public function __construct($numero_mensaje = '', $fecha_reporte = '', $nombre_firmante = '')
    {
        parent::__construct('P', 'mm', 'LETTER', true, 'UTF-8', false);
        global $GPV_Database;
        $this->database = $GPV_Database;
        // Inicializar las variables
        $this->numero_mensaje = $numero_mensaje;
        $this->fecha_reporte = $fecha_reporte;
        $this->nombre_firmante = $nombre_firmante;

        // Configurar documento
        $this->SetCreator('Gestión de Parque Vehicular v' . GPV_VERSION);
        $this->SetAuthor(get_bloginfo('name'));
        $this->SetTitle('Reporte de Movimientos - ' . $numero_mensaje);
        $this->SetSubject('Registro de movimientos de vehículos');

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
        // Logo
        $logo_url = GPV_PLUGIN_URL . 'assets/images/logo.png';
        if (file_exists(GPV_PLUGIN_DIR . 'assets/images/logo.png')) {
            $this->Image($logo_url, 15, 10, 30, '', 'PNG');
        }

        // Título principal
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'REPORTE DE MOVIMIENTOS', 0, 1, 'C');
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 5, 'Gestión de Parque Vehicular', 0, 1, 'C');

        // Información del reporte
        $this->SetFont('helvetica', '', 10);
        $this->Ln(5);
        $this->Cell(50, 6, 'Número de Referencia:', 0, 0, 'R');
        $this->Cell(50, 6, $this->numero_mensaje, 0, 1, 'L');

        $this->Cell(50, 6, 'Fecha del Reporte:', 0, 0, 'R');
        $this->Cell(50, 6, $this->fecha_reporte, 0, 1, 'L');

        // Línea horizontal
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 170, $this->GetY());
        $this->Ln(5);
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

    /**
     * Método para generar el reporte en PDF
     */
    public function generate_report($reporte_id)
    {
        global $GPV_Database;
        $this->database = $GPV_Database;

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

        // Obtener datos del firmante secundario
        $firmante2 = null;
        if (isset($reporte->firmante2_id) && $reporte->firmante2_id) {
            $firmantes2 = $this->database->get_firmantes(['id' => $reporte->firmante2_id]);
            if (!empty($firmantes2)) {
                $firmante2 = $firmantes2[0];
            }
        }

        // Formatear fecha
        $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reporte->fecha_reporte));

        // Crear el reporte PDF
        $this->numero_mensaje = $reporte->numero_mensaje;
        $this->fecha_reporte = $fecha_formateada;
        $this->nombre_firmante = $firmante ? $firmante->nombre : '';

        // Añadir página
        $this->AddPage();

        // Información del reporte
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Información General', 0, 1, 'L');
        $this->SetFont('helvetica', '', 10);

        $this->Cell(60, 8, 'Vehículo:', 0, 0, 'L');
        $this->Cell(0, 8, $reporte->vehiculo_siglas . ' - ' . $reporte->vehiculo_nombre, 0, 1, 'L');

        $this->Cell(60, 8, 'Conductor:', 0, 0, 'L');
        $this->Cell(0, 8, $reporte->conductor, 0, 1, 'L');

        $this->Cell(60, 8, 'Fecha de salida:', 0, 0, 'L');
        $this->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_inicial)) . ' ' .
            date('H:i', strtotime($reporte->hora_inicial)), 0, 1, 'L');

        $this->Cell(60, 8, 'Fecha de regreso:', 0, 0, 'L');
        $this->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_final)) . ' ' .
            date('H:i', strtotime($reporte->hora_final)), 0, 1, 'L');

        $this->Cell(60, 8, 'Odómetro inicial:', 0, 0, 'L');
        $this->Cell(0, 8, number_format($reporte->odometro_inicial, 2) . ' km', 0, 1, 'L');

        $this->Cell(60, 8, 'Odómetro final:', 0, 0, 'L');
        $this->Cell(0, 8, number_format($reporte->odometro_final, 2) . ' km', 0, 1, 'L');

        $this->Cell(60, 8, 'Distancia total:', 0, 0, 'L');
        $this->Cell(0, 8, number_format($reporte->distancia_total, 2) . ' km', 0, 1, 'L');

        // Mostrar propósito si existe
        if (isset($reporte->proposito) && !empty($reporte->proposito)) {
            $this->Cell(60, 8, 'Propósito:', 0, 0, 'L');
            $this->Cell(0, 8, $reporte->proposito, 0, 1, 'L');
        }

        // Detalles de movimientos incluidos
        $this->Ln(5);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Movimientos Incluidos', 0, 1, 'L');
        $this->SetFont('helvetica', '', 10);

        // Obtener IDs de movimientos
        $movimiento_ids = explode(',', $reporte->movimientos_incluidos);

        // Crear tabla para movimientos
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.1);
        $this->SetFont('helvetica', 'B', 8);

        // Encabezados
        $this->Cell(10, 7, 'ID', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Fecha', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Conductor', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Odóm. Inicial', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Odóm. Final', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Distancia', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Propósito', 1, 1, 'C', true);

        // Datos de movimientos
        $this->SetFont('helvetica', '', 8);

        foreach ($movimiento_ids as $movimiento_id) {
            // Obtener datos del movimiento
            $movimiento = $this->database->get_movement($movimiento_id);

            if ($movimiento) {
                $this->Cell(10, 6, $movimiento->id, 1, 0, 'C');
                $this->Cell(25, 6, date_i18n(get_option('date_format'), strtotime($movimiento->hora_salida)), 1, 0, 'C');
                $this->Cell(35, 6, $movimiento->conductor, 1, 0, 'L');
                $this->Cell(25, 6, number_format($movimiento->odometro_salida, 2), 1, 0, 'C');
                $this->Cell(25, 6, number_format($movimiento->odometro_entrada, 2), 1, 0, 'C');
                $this->Cell(25, 6, number_format($movimiento->distancia_recorrida, 2) . ' km', 1, 0, 'C');

                // Mostrar propósito del movimiento
                $proposito = isset($movimiento->proposito) ? $movimiento->proposito : '';
                // Recortar propósito si es muy largo
                if (strlen($proposito) > 30) {
                    $proposito = substr($proposito, 0, 27) . '...';
                }
                $this->Cell(40, 6, $proposito, 1, 1, 'L');
            }
        }

        // Sección de firmas
        $this->Ln(20);
        $this->SetFont('helvetica', '', 10);

        // Firma del conductor
        $this->Cell(80, 8, '____________________________', 0, 0, 'C');
        $this->Cell(80, 8, '____________________________', 0, 1, 'C');
        $this->Cell(80, 8, $reporte->conductor, 0, 0, 'C');
        $this->Cell(80, 8, $firmante ? $firmante->nombre : 'Firma Autorizada', 0, 1, 'C');
        $this->Cell(80, 8, 'Conductor', 0, 0, 'C');
        $this->Cell(80, 8, $firmante ? $firmante->cargo : '', 0, 1, 'C');

        // Añadir segunda firma si existe
        if ($firmante2) {
            $this->Ln(15);
            $this->Cell(0, 8, '____________________________', 0, 1, 'C');
            $this->Cell(0, 8, $firmante2->nombre, 0, 1, 'C');
            $this->Cell(0, 8, $firmante2->cargo, 0, 1, 'C');
        }

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
        $this->Output($filepath, 'F');

        // Actualizar estado del reporte
        $this->database->update_reporte_estado($reporte_id, 'generado', $filename);

        return $filepath;
    }
}
