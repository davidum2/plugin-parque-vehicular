<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class GPV_Maintenance {
    private $id;
    private $vehiculo_id;
    private $tipo;
    private $fecha_programada;
    private $fecha_realizada;
    private $odometro;
    private $descripcion;
    private $costo;
    private $proveedor;
    private $estado;
    private $comprobante_id;
    private $notas;
    private $creado_por;

    public function __construct($id = 0, $vehiculo_id = 0, $tipo = '', $fecha_programada = '', $fecha_realizada = '', $odometro = 0.0, $descripcion = '', $costo = 0.0, $proveedor = '', $estado = 'programado', $comprobante_id = 0, $notas = '', $creado_por = 0) {
        $this->id = $id;
        $this->vehiculo_id = $vehiculo_id;
        $this->tipo = $tipo;
        $this->fecha_programada = $fecha_programada;
        $this->fecha_realizada = $fecha_realizada;
        $this->odometro = $odometro;
        $this->descripcion = $descripcion;
        $this->costo = $costo;
        $this->proveedor = $proveedor;
        $this->estado = $estado;
        $this->comprobante_id = $comprobante_id;
        $this->notas = $notas;
        $this->creado_por = $creado_por;
    }

    // Métodos getter
    public function get_id() { return $this->id; }
    public function get_vehiculo_id() { return $this->vehiculo_id; }
    public function get_tipo() { return $this->tipo; }
    public function get_fecha_programada() { return $this->fecha_programada; }
    public function get_fecha_realizada() { return $this->fecha_realizada; }
    public function get_odometro() { return $this->odometro; }
    public function get_descripcion() { return $this->descripcion; }
    public function get_costo() { return $this->costo; }
    public function get_proveedor() { return $this->proveedor; }
    public function get_estado() { return $this->estado; }
    public function get_comprobante_id() { return $this->comprobante_id; }
    public function get_notas() { return $this->notas; }
    public function get_creado_por() { return $this->creado_por; }

    // Métodos setter
    public function set_id($id) { $this->id = $id; }
    public function set_vehiculo_id($vehiculo_id) { $this->vehiculo_id = $vehiculo_id; }
    public function set_tipo($tipo) { $this->tipo = $tipo; }
    public function set_fecha_programada($fecha_programada) { $this->fecha_programada = $fecha_programada; }
    public function set_fecha_realizada($fecha_realizada) { $this->fecha_realizada = $fecha_realizada; }
    public function set_odometro($odometro) { $this->odometro = $odometro; }
    public function set_descripcion($descripcion) { $this->descripcion = $descripcion; }
    public function set_costo($costo) { $this->costo = $costo; }
    public function set_proveedor($proveedor) { $this->proveedor = $proveedor; }
    public function set_estado($estado) { $this->estado = $estado; }
    public function set_comprobante_id($comprobante_id) { $this->comprobante_id = $comprobante_id; }
    public function set_notas($notas) { $this->notas = $notas; }
    public function set_creado_por($creado_por) { $this->creado_por = $creado_por; }
}
?>
