<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class GPV_Movement {
    private $id;
    private $vehiculo_siglas;
    private $vehiculo_nombre;
    private $odometro_salida;
    private $hora_salida;
    private $odometro_entrada;
    private $hora_entrada;
    private $distancia_recorrida;
    private $combustible_consumido;
    private $nivel_combustible;
    private $conductor;

    public function __construct($id = 0, $vehiculo_siglas = '', $vehiculo_nombre = '', $odometro_salida = 0.0, $hora_salida = '', $odometro_entrada = 0.0, $hora_entrada = '', $distancia_recorrida = 0.0, $combustible_consumido = 0.0, $nivel_combustible = 0.0, $conductor = '') {
        $this->id = $id;
        $this->vehiculo_siglas = $vehiculo_siglas;
        $this->vehiculo_nombre = $vehiculo_nombre;
        $this->odometro_salida = $odometro_salida;
        $this->hora_salida = $hora_salida;
        $this->odometro_entrada = $odometro_entrada;
        $this->hora_entrada = $hora_entrada;
        $this->distancia_recorrida = $distancia_recorrida;
        $this->combustible_consumido = $combustible_consumido;
        $this->nivel_combustible = $nivel_combustible;
        $this->conductor = $conductor;
    }

    // Métodos getter
    public function get_id() { return $this->id; }
    public function get_vehiculo_siglas() { return $this->vehiculo_siglas; }
    public function get_vehiculo_nombre() { return $this->vehiculo_nombre; }
    public function get_odometro_salida() { return $this->odometro_salida; }
    public function get_hora_salida() { return $this->hora_salida; }
    public function get_odometro_entrada() { return $this->odometro_entrada; }
    public function get_hora_entrada() { return $this->hora_entrada; }
    public function get_distancia_recorrida() { return $this->distancia_recorrida; }
    public function get_combustible_consumido() { return $this->combustible_consumido; }
    public function get_nivel_combustible() { return $this->nivel_combustible; }
    public function get_conductor() { return $this->conductor; }

    // Métodos setter
    public function set_id($id) { $this->id = $id; }
    public function set_vehiculo_siglas($vehiculo_siglas) { $this->vehiculo_siglas = $vehiculo_siglas; }
    public function set_vehiculo_nombre($vehiculo_nombre) { $this->vehiculo_nombre = $vehiculo_nombre; }
    public function set_odometro_salida($odometro_salida) { $this->odometro_salida = $odometro_salida; }
    public function set_hora_salida($hora_salida) { $this->hora_salida = $hora_salida; }
    public function set_odometro_entrada($odometro_entrada) { $this->odometro_entrada = $odometro_entrada; }
    public function set_hora_entrada($hora_entrada) { $this->hora_entrada = $hora_entrada; }
    public function set_distancia_recorrida($distancia_recorrida) { $this->distancia_recorrida = $distancia_recorrida; }
    public function set_combustible_consumido($combustible_consumido) { $this->combustible_consumido = $combustible_consumido; }
    public function set_nivel_combustible($nivel_combustible) { $this->nivel_combustible = $nivel_combustible; }
    public function set_conductor($conductor) { $this->conductor = $conductor; }
}
?>
