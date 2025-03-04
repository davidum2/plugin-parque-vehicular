// Modificación a includes/reports/class-gpv-report-pdf.php
public function generate_report($reporte_id)
{
// Obtener datos del reporte
$reporte = $this->database->get_reporte($reporte_id);

if (!$reporte) {
return false;
}

// Obtener datos de los firmantes
$firmante = null;
$firmante2 = null;

if ($reporte->firmante_id) {
$firmantes = $this->database->get_firmantes(['id' => $reporte->firmante_id]);
if (!empty($firmantes)) {
$firmante = $firmantes[0];
}
}

if (isset($reporte->firmante2_id) && $reporte->firmante2_id) {
$firmantes2 = $this->database->get_firmantes(['id' => $reporte->firmante2_id]);
if (!empty($firmantes2)) {
$firmante2 = $firmantes2[0];
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

$pdf->Cell(60, 8, 'Fecha de salida:', 0, 0, 'L');
$pdf->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_inicial)) . ' ' .
date('H:i', strtotime($reporte->hora_inicial)), 0, 1, 'L');

$pdf->Cell(60, 8, 'Fecha de regreso:', 0, 0, 'L');
$pdf->Cell(0, 8, date_i18n(get_option('date_format'), strtotime($reporte->fecha_final)) . ' ' .
date('H:i', strtotime($reporte->hora_final)), 0, 1, 'L');

$pdf->Cell(60, 8, 'Odómetro inicial:', 0, 0, 'L');
$pdf->Cell(0, 8, number_format($reporte->odometro_inicial, 2) . ' km', 0, 1, 'L');

$pdf->Cell(60, 8, 'Odómetro final:', 0, 0, 'L');
$pdf->Cell(0, 8, number_format($reporte->odometro_final, 2) . ' km', 0, 1, 'L');

$pdf->Cell(60, 8, 'Distancia total:', 0, 0, 'L');
$pdf->Cell(0, 8, number_format($reporte->distancia_total, 2) . ' km', 0, 1, 'L');

// Detalles de movimientos
// [código existente para los detalles...]

// Sección de firmas
$pdf->Ln(20);

// Firmas en tres columnas: Conductor, Firmante 1, Firmante 2
$pdf->SetFont('helvetica', '', 10);

// Primera firma (Conductor)
$pdf->Cell(60, 8, '____________________________', 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C'); // Espacio

// Segunda firma (Firmante 1)
$pdf->Cell(60, 8, '____________________________', 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C'); // Espacio

// Tercera firma (Firmante 2) si existe
if ($firmante2) {
$pdf->Cell(60, 8, '____________________________', 0, 1, 'C');
} else {
$pdf->Cell(0, 8, '', 0, 1, 'C');
}

// Nombres
$pdf->Cell(60, 8, $reporte->conductor, 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C');
$pdf->Cell(60, 8, $firmante ? $firmante->nombre : 'Firma Autorizada', 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C');
if ($firmante2) {
$pdf->Cell(60, 8, $firmante2->nombre, 0, 1, 'C');
} else {
$pdf->Cell(0, 8, '', 0, 1, 'C');
}

// Cargos
$pdf->Cell(60, 8, 'Conductor', 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C');
$pdf->Cell(60, 8, $firmante ? $firmante->cargo : '', 0, 0, 'C');
$pdf->Cell(10, 8, '', 0, 0, 'C');
if ($firmante2) {
$pdf->Cell(60, 8, $firmante2->cargo, 0, 1, 'C');
} else {
$pdf->Cell(0, 8, '', 0, 1, 'C');
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
$pdf->Output($filepath, 'F');

// Actualizar estado del reporte
$this->database->update_reporte_estado($reporte_id, 'generado', $filename);

return $filepath;
}
