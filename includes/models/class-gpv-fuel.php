<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class GPV_Fuel {
    private $id;
    private $vehiculo_siglas;
    private $vehiculo_nombre;
    private $odometro_carga;
    private $litros_cargados;
    private $precio;
    private $km_desde_ultima_carga;
    private $factor_consumo;

    public function __construct($id = 0, $vehiculo_siglas = '', $vehiculo_nombre = '', $odometro_carga = 0.0, $litros_cargados = 0.0, $precio = 0.0, $km_desde_ultima_carga = 0.0, $factor_consumo = 0.0) {
        $this->id = $id;
        $this->vehiculo_siglas = $vehiculo_siglas;
        $this->vehiculo_nombre = $vehiculo_nombre;
        $this->odometro_carga = $odometro_carga;
        $this->litros_cargados = $litros_cargados;
        $this->precio = $precio;
        $this->km_desde_ultima_carga = $km_desde_ultima_carga;
        $this->factor_consumo = $factor_consumo;
    }

    // Métodos getter
    public function get_id() { return $this->id; }
    public function get_vehiculo_siglas() { return $this->vehiculo_siglas; }
    public function get_vehiculo_nombre() { return $this->vehiculo_nombre; }
    public function get_odometro_carga() { return $this->odometro_carga; }
    public function get_litros_cargados() { return $this->litros_cargados; }
    public function get_precio() { return $this->precio; }
    public function get_km_desde_ultima_carga() { return $this->km_desde_ultima_carga; }
    public function get_factor_consumo() { return $this->factor_consumo; }

    // Métodos setter
    public function set_id($id) { $this->id = $id; }
    public function set_vehiculo_siglas($vehiculo_siglas) { $this->vehiculo_siglas = $vehiculo_siglas; }
    public function set_vehiculo_nombre($vehiculo_nombre) { $this->vehiculo_nombre = $vehiculo_nombre; }
    public function set_odometro_carga($odometro_carga) { $this->odometro_carga = $odometro_carga; }
    public function set_litros_cargados($litros_cargados) { $this->litros_cargados = $litros_cargados; }
    public function set_precio($precio) { $this->precio = $precio; }
    public function set_km_desde_ultima_carga($km_desde_ultima_carga) { $this->km_desde_ultima_carga = $km_desde_ultima_carga; }
    public function set_factor_consumo($factor_consumo) { $this->factor_consumo = $factor_consumo; }
}
?>