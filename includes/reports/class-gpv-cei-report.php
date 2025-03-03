<?php

/**
 * Clase para generar reportes de tipo C.E.I.
 *
 * @package GPV
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Verificar que TCPDF está disponible (ajusta la ruta según donde esté tu TCPDF)
require_once GPV_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';

/**
 * Clase extendida de TCPDF para reportes C.E.I.
 */
class GPV_CEI_PDF extends TCPDF
{
    // Variables para el encabezado
    protected $mensaje_numero;
    protected $mensaje_fecha;
    protected $mensaje_ref;

    /**
     * Constructor
     */
    public function __construct($mensaje_numero = '', $mensaje_fecha = '', $mensaje_ref = '')
    {
        parent::__construct('P', 'mm', 'LETTER', true, 'UTF-8', false);

        // Inicializar las variables del mensaje
        $this->mensaje_numero = $mensaje_numero;
        $this->mensaje_fecha = $mensaje_fecha;
        $this->mensaje_ref = $mensaje_ref;

        // Configurar documento
        $this->SetCreator('Gestión de Parque Vehicular v' . GPV_VERSION);
        $this->SetAuthor(get_bloginfo('name'));
        $this->SetTitle('Mensaje C.E.I. - ' . $mensaje_numero);
        $this->SetSubject('Reincorporada de vehículos');

        // Configurar márgenes
        $this->SetMargins(25, 45, 25);
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
        // Título principal
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 6, 'Mensaje C.E.I.', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 6, '"Urgente"', 0, 1, 'R');

        $this->Ln(2);

        // Línea horizontal
        $this->SetDrawColor(0, 0, 0);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 170, $this->GetY());

        // Información del mensaje
        $this->SetFont('helvetica', '', 10);
        $this->Ln(5);
        $this->Cell(100, 6, '', 0, 0, 'L');
        $this->Cell(30, 6, 'Numero:', 0, 0, 'R');
        $this->Cell(40, 6, $this->mensaje_numero, 0, 1, 'L');

        $this->Cell(100, 6, '', 0, 0, 'L');
        $this->Cell(30, 6, 'Hoja:', 0, 0, 'R');
        $this->Cell(40, 6, '1/1', 0, 1, 'L');

        $this->Cell(100, 6, '', 0, 0, 'L');
        $this->Cell(30, 6, 'Fecha:', 0, 0, 'R');
        $this->Cell(40, 6, $this->mensaje_fecha, 0, 1, 'L');

        $this->Cell(100, 6, '', 0, 0, 'L');
        $this->Cell(30, 6, 'Ref:', 0, 0, 'R');
        $this->Cell(40, 6, $this->mensaje_ref, 0, 1, 'L');

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
}

/**
 * Clase principal para generar el reporte C.E.I.
 */
class GPV_CEI_Report
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
     * Generar reporte C.E.I. para un movimiento específico
     *
     * @param int $movement_id ID del movimiento
     */
    public function generate_cei_report($movement_id)
    {
        // Obtener datos del movimiento
        $movement = $this->database->get_movement($movement_id);

        if (!$movement) {
            wp_die(__('El movimiento especificado no existe.', 'gestion-parque-vehicular'));
        }

        // Obtener datos del vehículo
        $vehicle = $this->database->get_vehicle($movement->vehiculo_id);

        if (!$vehicle) {
            wp_die(__('El vehículo asociado al movimiento no existe.', 'gestion-parque-vehicular'));
        }

        // Formatear fechas
        $fecha_salida = date_i18n('j \d\e M\. Y', strtotime($movement->hora_salida));
        $hora_salida = date('Hi', strtotime($movement->hora_salida));

        $fecha_entrada = empty($movement->hora_entrada) ? 'N/A' : date_i18n('j \d\e M\. Y', strtotime($movement->hora_entrada));
        $hora_entrada = empty($movement->hora_entrada) ? 'N/A' : date('Hi', strtotime($movement->hora_entrada));

        // Preparar información para el reporte
        $mensaje_numero = 'Tptes. 03';
        $mensaje_fecha = $fecha_salida;
        $mensaje_ref = 'Reincorporada de vehículos';

        // Crear el reporte PDF
        $pdf = new GPV_CEI_PDF($mensaje_numero, $mensaje_fecha, $mensaje_ref);

        // Añadir página
        $pdf->AddPage();

        // Destinatarios
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, 'Cnto. Nal. Adto. (Tren de Tptes.). -- ');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Campo Mil. No. 42-A ');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, '"Gral. Div. Francisco Villa", ');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Santa Gertrudis, Chih.');
        $pdf->Ln(8);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, 'C.G. 42/a. Z.M. E.M. (S-2). -- ');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Campo Mil. No. 42-B ');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Write(6, '"Gral. Brig Maclovio Herrera Cano", ');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Write(6, 'Hgo., del Parral, Chih.');
        $pdf->Ln(12);

        // Párrafo explicativo
        $pdf->SetFont('helvetica', '', 10);
        $parrafo = 'En cumplimiento a lo ordenado en el Msje. C.E.I. No. S-2 (M-2)-15467/28177, de 27 de Oct., 2021, No. CyL/4530 de 25 Oct. 2021 y el Msje. C.E.I. No. Admtva/CyL/03690 de 6 Ago. 2023, referente a los lineamientos para comprobar el ejercicio de los recursos de la partida presupuestal 26101 "Combustibles, Lubricantes y Aditivos para Vehículos Terrestres, Aéreos, Marítimos, Lacustres y Fluviales destinados a la ejecución de Programas de Seguridad Pública y Nacional", me permito informar los odómetros de salida de los vehículos de cargo en este organismo, como sigue:';
        $pdf->MultiCell(0, 6, $parrafo, 0, 'J', false);
        $pdf->Ln(5);

        // Tabla de movimientos
        $pdf->SetFont('helvetica', 'B', 8);

        // Cabecera de la tabla
        $pdf->Cell(18, 10, 'Tipo de vehículo', 1, 0, 'C');
        $pdf->Cell(15, 10, 'Siglas', 1, 0, 'C');
        $pdf->Cell(18, 10, 'Odóm. Inicial', 1, 0, 'C');
        $pdf->Cell(18, 10, 'Odóm. Final', 1, 0, 'C');
        $pdf->Cell(15, 10, 'Kms. Recorridos', 1, 0, 'C');
        $pdf->Cell(22, 10, 'Fecha de salida', 1, 0, 'C');
        $pdf->Cell(15, 10, 'Hora de salida', 1, 0, 'C');
        $pdf->Cell(22, 10, 'Fecha de entrada', 1, 0, 'C');
        $pdf->Cell(15, 10, 'Hora de entrada', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Servicio desempeñado', 1, 1, 'C');

        // Datos del movimiento
        $pdf->SetFont('helvetica', '', 8);

        // Calcular la distancia recorrida
        $distancia = !empty($movement->distancia_recorrida) ? number_format($movement->distancia_recorrida, 0) : ((!empty($movement->odometro_entrada) && !empty($movement->odometro_salida)) ?
            number_format($movement->odometro_entrada - $movement->odometro_salida, 0) : 'N/A');

        $pdf->Cell(18, 6, $vehicle->categoria, 1, 0, 'C');
        $pdf->Cell(15, 6, $vehicle->siglas, 1, 0, 'C');
        $pdf->Cell(18, 6, number_format($movement->odometro_salida, 0), 1, 0, 'C');
        $pdf->Cell(18, 6, !empty($movement->odometro_entrada) ? number_format($movement->odometro_entrada, 0) : 'N/A', 1, 0, 'C');
        $pdf->Cell(15, 6, $distancia, 1, 0, 'C');
        $pdf->Cell(22, 6, $fecha_salida, 1, 0, 'C');
        $pdf->Cell(15, 6, $hora_salida, 1, 0, 'C');
        $pdf->Cell(22, 6, $fecha_entrada, 1, 0, 'C');
        $pdf->Cell(15, 6, $hora_entrada, 1, 0, 'C');
        $pdf->Cell(30, 6, !empty($movement->proposito) ? $movement->proposito : 'N/A', 1, 1, 'C');

        $pdf->Ln(10);

        // Firmas
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, '.- Atte.', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->Cell(0, 6, 'Gral. Bgda. E.M. S.A. Sánchez Garcia.-Cmte.', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'P. L. F.', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->Cell(0, 6, 'El Cor. Cab. E.M., Subjefe Admtvo.', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->Cell(0, 6, 'Rafael López Rodríguez.', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->Cell(0, 6, '(B-5767973)', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->Cell(0, 6, 'RLR-ACS-ovb-alt.', 0, 1, 'L');

        // Salida del PDF
        $filename = 'Mensaje_CEI_' . $mensaje_numero . '_' . date('Ymd') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
