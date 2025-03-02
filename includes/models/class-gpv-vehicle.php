<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class GPV_Vehicle {
    private $id;
    private $siglas;
    private $anio;
    private $nombre_vehiculo;
    private $odometro_actual;
    private $nivel_combustible;
    private $tipo_combustible;
    private $medida_odometro;
    private $factor_consumo;
    private $capacidad_tanque;
    private $ubicacion_actual;
    private $categoria;

    public function __construct($id = 0, $siglas = '', $anio = 0, $nombre_vehiculo = '', $odometro_actual = 0.0, $nivel_combustible = 0.0, $tipo_combustible = '', $medida_odometro = '', $factor_consumo = 0.0, $capacidad_tanque = 0.0, $ubicacion_actual = '', $categoria = '') {
        $this->id = $id;
        $this->siglas = $siglas;
        $this->anio = $anio;
        $this->nombre_vehiculo = $nombre_vehiculo;
        $this->odometro_actual = $odometro_actual;
        $this->nivel_combustible = $nivel_combustible;
        $this->tipo_combustible = $tipo_combustible;
        $this->medida_odometro = $medida_odometro;
        $this->factor_consumo = $factor_consumo;
        $this->capacidad_tanque = $capacidad_tanque;
        $this->ubicacion_actual = $ubicacion_actual;
        $this->categoria = $categoria;
    }

    // Métodos getter
    public function get_id() { return $this->id; }
    public function get_siglas() { return $this->siglas; }
    public function get_anio() { return $this->anio; }
    public function get_nombre_vehiculo() { return $this->nombre_vehiculo; }
    public function get_odometro_actual() { return $this->odometro_actual; }
    public function get_nivel_combustible() { return $this->nivel_combustible; }
    public function get_tipo_combustible() { return $this->tipo_combustible; }
    public function get_medida_odometro() { return $this->medida_odometro; }
    public function get_factor_consumo() { return $this->factor_consumo; }
    public function get_capacidad_tanque() { return $this->capacidad_tanque; }
    public function get_ubicacion_actual() { return $this->ubicacion_actual; }
    public function get_categoria() { return $this->categoria; }

    // Métodos setter
    public function set_id($id) { $this->id = $id; }
    public function set_siglas($siglas) { $this->siglas = $siglas; }
    public function set_anio($anio) { $this->anio = $anio; }
    public function set_nombre_vehiculo($nombre_vehiculo) { $this->nombre_vehiculo = $nombre_vehiculo; }
    public function set_odometro_actual($odometro_actual) { $this->odometro_actual = $odometro_actual; }
    public function set_nivel_combustible($nivel_combustible) { $this->nivel_combustible = $nivel_combustible; }
    public function set_tipo_combustible($tipo_combustible) { $this->tipo_combustible = $tipo_combustible; }
    public function set_medida_odometro($medida_odometro) { $this->medida_odometro = $medida_odometro; }
    public function set_factor_consumo($factor_consumo) { $this->factor_consumo = $factor_consumo; }
    public function set_capacidad_tanque($capacidad_tanque) { $this->capacidad_tanque = $capacidad_tanque; }
    public function set_ubicacion_actual($ubicacion_actual) { $this->ubicacion_actual = $ubicacion_actual; }
    public function set_categoria($categoria) { $this->categoria = $categoria; }
}
?>
