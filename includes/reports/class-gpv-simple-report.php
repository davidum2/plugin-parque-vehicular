<?php

/**
 * Clase para un reporte simple con TCPDF
 *
 * @package GPV
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para generar un reporte simple
 */
class GPV_Simple_Report
{

    /**
     * Generar un reporte PDF simple que dice "Hello World"
     */
    public function generate_hello_world()
    {
        // Incluir la biblioteca TCPDF completa
        require_once GPV_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';

        // Crear nuevo documento PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Establecer información del documento
        $pdf->SetCreator('Gestión de Parque Vehicular');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Reporte Simple');
        $pdf->SetSubject('Hello World PDF');
        $pdf->SetKeywords('TCPDF, PDF, Reporte, Hello World');

        // Eliminar cabecera y pie de página predeterminados
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Establecer márgenes
        $pdf->SetMargins(20, 20, 20);

        // Añadir una página
        $pdf->AddPage();

        // Establecer fuente
        $pdf->SetFont('helvetica', 'B', 20);

        // Título centrado
        $pdf->Cell(0, 15, 'Mi Primer Reporte PDF', 0, 1, 'C');

        // Cambiar a fuente normal
        $pdf->SetFont('helvetica', '', 14);

        // Texto "Hello World"
        $pdf->Ln(10); // Salto de línea
        $pdf->Cell(0, 10, 'Hello World desde Gestión de Parque Vehicular', 0, 1, 'C');

        // Fecha actual
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'Generado el: ' . date_i18n(get_option('date_format')), 0, 1, 'C');

        // Salida del PDF (descarga)
        $pdf->Output('hello-world.pdf', 'D');
        exit;
    }
}
