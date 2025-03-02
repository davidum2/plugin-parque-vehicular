(function ($) {
  $(document).ready(function () {
    console.log('El plugin de Gestión de Parque Vehicular está listo.');

    // Inicializar la fecha y hora actual en los campos datetime-local
    if ($('#hora_salida').length > 0) {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');

      const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
      $('#hora_salida').val(formattedDateTime);
      $('#hora_entrada').val(formattedDateTime);
    }

    // Funcionalidad para el formulario de movimientos
    if ($('#gpv-movimiento-form').length > 0) {
      // Cuando se selecciona un vehículo
      $('#vehiculo_id').change(function () {
        const selectedOption = $(this).find('option:selected');
        const odometro = selectedOption.data('odometro');
        const combustible = selectedOption.data('combustible');

        // Establecer el odómetro de salida con el valor actual del vehículo
        if (odometro) {
          $('#odometro_salida').val(odometro);
        }

        // Calcular valores iniciales
        calcularValores();
      });

      // Cuando cambia el odómetro de entrada
      $('#odometro_entrada').on('input', function () {
        calcularValores();
      });

      // Función para calcular valores
      function calcularValores() {
        const selectedOption = $('#vehiculo_id').find('option:selected');
        const odometroSalida = parseFloat($('#odometro_salida').val()) || 0;
        const odometroEntrada = parseFloat($('#odometro_entrada').val()) || 0;
        const factorConsumo = parseFloat(selectedOption.data('factor')) || 0;
        const nivelCombustible =
          parseFloat(selectedOption.data('combustible')) || 0;

        // Calcular distancia
        const distancia = odometroEntrada - odometroSalida;
        $('#distancia_calculada').text(distancia.toFixed(2));

        // Calcular combustible consumido
        let combustibleConsumido = 0;
        if (factorConsumo > 0) {
          combustibleConsumido = distancia / factorConsumo;
        }
        $('#combustible_calculado').text(combustibleConsumido.toFixed(2));

        // Calcular nivel final de combustible
        let nivelFinal = nivelCombustible - combustibleConsumido;
        if (nivelFinal < 0) nivelFinal = 0;
        $('#nivel_final').text(nivelFinal.toFixed(2));

        // Mostrar advertencia si el nivel de combustible es bajo
        if (nivelFinal < 5) {
          $('#nivel_final').css('color', 'red');
        } else {
          $('#nivel_final').css('color', '');
        }
      }
    }
  });

  // Validación de formularios
  $('.gpv-form').submit(function (event) {
    let isValid = true;

    // Validar campos requeridos
    $(this)
      .find('[required]')
      .each(function () {
        if ($(this).val().trim() === '') {
          const fieldName = $(this).prev('label').text() || 'Este campo';
          alert(fieldName + ' es requerido.');
          $(this).focus();
          isValid = false;
          event.preventDefault();
          return false;
        }
      });

    // Validar que el odómetro de entrada sea mayor que el de salida
    if ($('#odometro_entrada').length > 0 && $('#odometro_salida').length > 0) {
      const odometroSalida = parseFloat($('#odometro_salida').val());
      const odometroEntrada = parseFloat($('#odometro_entrada').val());

      if (odometroEntrada <= odometroSalida) {
        alert('El odómetro de entrada debe ser mayor que el de salida.');
        $('#odometro_entrada').focus();
        isValid = false;
        event.preventDefault();
      }
    }

    return isValid;
  });
})(jQuery);
