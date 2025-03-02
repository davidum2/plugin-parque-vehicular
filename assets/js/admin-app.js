/**
 * Scripts de administración para GPV
 */
(function ($) {
  'use strict';

  // Objeto principal para la aplicación admin
  const GPVAdmin = {
    // Inicialización
    init: function () {
      this.setupTabs();
      this.setupForms();
      this.setupDashboard();
      this.setupMaintenanceAlerts();
      this.setupChangeListeners();
    },

    // Configurar pestañas en la interfaz
    setupTabs: function () {
      $('.gpv-admin-tabs').on('click', '.gpv-tab', function (e) {
        e.preventDefault();
        const $this = $(this);
        const tabTarget = $this.data('tab');

        // Activar pestaña seleccionada
        $('.gpv-tab').removeClass('active');
        $this.addClass('active');

        // Mostrar contenido de la pestaña
        $('.gpv-tab-content').hide();
        $(`#${tabTarget}`).show();

        // Guardar preferencia de pestaña en localStorage
        if (typeof Storage !== 'undefined') {
          localStorage.setItem('gpvActiveTab', tabTarget);
        }
      });

      // Cargar pestaña guardada o la primera por defecto
      if (
        typeof Storage !== 'undefined' &&
        localStorage.getItem('gpvActiveTab')
      ) {
        const savedTab = localStorage.getItem('gpvActiveTab');
        $(`.gpv-tab[data-tab="${savedTab}"]`).trigger('click');
      } else {
        // Activar la primera pestaña por defecto
        $('.gpv-tab:first').trigger('click');
      }
    },

    // Configurar formularios
    setupForms: function () {
      // Validación de formularios
      $('.gpv-admin-form').on('submit', function (e) {
        let isValid = true;
        const $form = $(this);

        // Validar campos requeridos
        $form.find('[required]').each(function () {
          const $field = $(this);
          if ($field.val().trim() === '') {
            isValid = false;
            $field.addClass('gpv-error-field');

            // Crear mensaje de error si no existe
            if ($field.next('.gpv-error-message').length === 0) {
              const fieldName =
                $field.closest('tr').find('th label').text() || 'Este campo';
              $field.after(
                `<span class="gpv-error-message">${fieldName} es requerido</span>`
              );
            }
          } else {
            $field.removeClass('gpv-error-field');
            $field.next('.gpv-error-message').remove();
          }
        });

        // Validaciones específicas para movimientos
        if ($form.hasClass('gpv-form-movement')) {
          // Validar que odómetro entrada > odómetro salida
          const odometroSalida = parseFloat($('#odometro_salida').val());
          const odometroEntrada = parseFloat($('#odometro_entrada').val());

          if (odometroEntrada <= odometroSalida) {
            isValid = false;
            $('#odometro_entrada').addClass('gpv-error-field');

            if (
              $('#odometro_entrada').next('.gpv-error-message').length === 0
            ) {
              $('#odometro_entrada').after(
                '<span class="gpv-error-message">El odómetro de entrada debe ser mayor que el de salida</span>'
              );
            }
          }
        }

        if (!isValid) {
          e.preventDefault();

          // Scroll al primer error
          const $firstError = $('.gpv-error-field:first');
          if ($firstError.length) {
            $('html, body').animate(
              {
                scrollTop: $firstError.offset().top - 100,
              },
              500
            );
          }
        }
      });

      // Reiniciar validación al cambiar campos
      $(document).on('change input', '.gpv-error-field', function () {
        $(this).removeClass('gpv-error-field');
        $(this).next('.gpv-error-message').remove();
      });

      // Datepickers para campos de fecha
      if ($.fn.datepicker) {
        $('.gpv-datepicker').datepicker({
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
        });
      }
    },

    // Configurar dashboard
    setupDashboard: function () {
      const $dashboard = $('#gpv-admin-dashboard');
      if (!$dashboard.length) return;

      // Cargar datos del dashboard vía AJAX
      this.loadDashboardData();

      // Configurar filtros de fecha
      $('#gpv-date-filter').on('change', function () {
        GPVAdmin.loadDashboardData($(this).val());
      });

      // Configurar selector de gráficos
      $('#gpv-chart-type').on('change', function () {
        GPVAdmin.updateCharts($(this).val());
      });

      // Configurar refresco automático cada 5 minutos
      setInterval(function () {
        GPVAdmin.loadDashboardData($('#gpv-date-filter').val());
      }, 300000); // 5 minutos
    },

    // Cargar datos del dashboard
    loadDashboardData: function (dateFilter = 'month') {
      const $dashboard = $('#gpv-admin-dashboard');
      if (!$dashboard.length) return;

      $dashboard.addClass('gpv-loading');

      // Realizar petición AJAX
      $.ajax({
        url: gpvAdminData.ajax_url,
        type: 'POST',
        data: {
          action: 'gpv_get_dashboard_data',
          nonce: gpvAdminData.nonce,
          filter: dateFilter,
        },
        success: function (response) {
          if (response.success) {
            GPVAdmin.renderDashboardStats(response.data);
          } else {
            console.error('Error cargando datos del dashboard:', response.data);
          }
          $dashboard.removeClass('gpv-loading');
        },
        error: function (xhr, status, error) {
          console.error('Error AJAX:', error);
          $dashboard.removeClass('gpv-loading');
        },
      });
    },

    // Renderizar estadísticas del dashboard
    renderDashboardStats: function (data) {
      // Actualizar estadísticas de vehículos
      $('#gpv-total-vehicles').text(data.vehicles.total);
      $('#gpv-available-vehicles').text(data.vehicles.available);
      $('#gpv-in-use-vehicles').text(data.vehicles.in_use);
      $('#gpv-maintenance-vehicles').text(data.vehicles.maintenance);

      // Actualizar estadísticas de movimientos
      $('#gpv-movements-today').text(data.movements.today);
      $('#gpv-movements-month').text(data.movements.month);
      $('#gpv-total-distance').text(
        data.movements.total_distance.toFixed(2) + ' km'
      );
      $('#gpv-active-movements').text(data.movements.active);

      // Actualizar estadísticas de combustible
      $('#gpv-fuel-consumption').text(
        data.fuel.month_consumption.toFixed(2) + ' L'
      );
      $('#gpv-fuel-cost').text('$' + data.fuel.month_cost.toFixed(2));
      $('#gpv-average-consumption').text(
        data.fuel.average_consumption.toFixed(2) + ' km/L'
      );
      $('#gpv-fuel-total-year').text('$' + data.fuel.total_year.toFixed(2));

      // Actualizar estadísticas de mantenimiento
      $('#gpv-pending-maintenance').text(data.maintenance.pending);
      $('#gpv-upcoming-maintenance').text(data.maintenance.upcoming);
      $('#gpv-completed-maintenance').text(data.maintenance.completed);
      $('#gpv-maintenance-cost').text(
        '$' + data.maintenance.month_cost.toFixed(2)
      );

      // Actualizar hora de última actualización
      $('.gpv-last-update').text(
        'Última actualización: ' + new Date(data.last_update).toLocaleString()
      );

      // Actualizar gráficos si existen
      if (typeof Chart !== 'undefined') {
        this.updateCharts($('#gpv-chart-type').val(), data);
      }
    },

    // Actualizar gráficos
    updateCharts: function (chartType = 'vehicles', data = null) {
      if (typeof Chart === 'undefined') return;

      // Si no hay datos, cargarlos
      if (!data) {
        this.loadDashboardData($('#gpv-date-filter').val());
        return;
      }

      // Eliminar gráficos existentes
      if (window.gpvCharts) {
        for (const chartKey in window.gpvCharts) {
          if (window.gpvCharts[chartKey]) {
            window.gpvCharts[chartKey].destroy();
          }
        }
      }

      // Inicializar objeto para gráficos
      window.gpvCharts = window.gpvCharts || {};

      // Gráfico para vehículos
      if (chartType === 'vehicles' || chartType === 'all') {
        const ctx = document.getElementById('gpv-vehicles-chart');
        if (ctx) {
          window.gpvCharts.vehicles = new Chart(ctx, {
            type: 'pie',
            data: {
              labels: ['Disponibles', 'En uso', 'Mantenimiento'],
              datasets: [
                {
                  data: [
                    data.vehicles.available,
                    data.vehicles.in_use,
                    data.vehicles.maintenance,
                  ],
                  backgroundColor: ['#4CAF50', '#2196F3', '#FFC107'],
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                },
                title: {
                  display: true,
                  text: 'Estado de Vehículos',
                },
              },
            },
          });
        }
      }

      // Gráfico para combustible
      if (chartType === 'fuel' || chartType === 'all') {
        const ctx = document.getElementById('gpv-fuel-chart');
        if (ctx && data.fuel.monthly_data) {
          const labels = data.fuel.monthly_data.map((item) => item.month);
          const consumption = data.fuel.monthly_data.map(
            (item) => item.consumption
          );
          const cost = data.fuel.monthly_data.map((item) => item.cost);

          window.gpvCharts.fuel = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Consumo (L)',
                  data: consumption,
                  backgroundColor: '#2196F3',
                  order: 2,
                },
                {
                  label: 'Costo ($)',
                  data: cost,
                  backgroundColor: '#4CAF50',
                  type: 'line',
                  order: 1,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                title: {
                  display: true,
                  text: 'Consumo y Costo de Combustible',
                },
              },
              scales: {
                y: {
                  beginAtZero: true,
                },
              },
            },
          });
        }
      }

      // Gráfico para movimientos
      if (chartType === 'movements' || chartType === 'all') {
        const ctx = document.getElementById('gpv-movements-chart');
        if (ctx && data.movements.daily_data) {
          const labels = data.movements.daily_data.map((item) => item.date);
          const count = data.movements.daily_data.map((item) => item.count);
          const distance = data.movements.daily_data.map(
            (item) => item.distance
          );

          window.gpvCharts.movements = new Chart(ctx, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Cantidad',
                  data: count,
                  borderColor: '#2196F3',
                  backgroundColor: 'rgba(33, 150, 243, 0.1)',
                  yAxisID: 'y',
                },
                {
                  label: 'Distancia (km)',
                  data: distance,
                  borderColor: '#FF9800',
                  backgroundColor: 'rgba(255, 152, 0, 0.1)',
                  yAxisID: 'y1',
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                title: {
                  display: true,
                  text: 'Movimientos Diarios',
                },
              },
              scales: {
                y: {
                  type: 'linear',
                  display: true,
                  position: 'left',
                  beginAtZero: true,
                },
                y1: {
                  type: 'linear',
                  display: true,
                  position: 'right',
                  beginAtZero: true,
                  grid: {
                    drawOnChartArea: false,
                  },
                },
              },
            },
          });
        }
      }
    },

    // Configurar alertas de mantenimiento
    setupMaintenanceAlerts: function () {
      const $maintenanceWidget = $('#gpv-maintenance-alerts');
      if (!$maintenanceWidget.length) return;

      // Cargar alertas de mantenimiento
      $.ajax({
        url: gpvAdminData.ajax_url,
        type: 'POST',
        data: {
          action: 'gpv_get_maintenance_alerts',
          nonce: gpvAdminData.nonce,
        },
        success: function (response) {
          if (response.success && response.data.alerts.length > 0) {
            let alertsHtml = '';

            response.data.alerts.forEach(function (alert) {
              // Determinar clase de alerta según prioridad
              let alertClass = 'info';
              if (alert.priority === 'high') {
                alertClass = 'danger';
              } else if (alert.priority === 'medium') {
                alertClass = 'warning';
              }

              // Construir HTML de la alerta
              alertsHtml += `
                <div class="gpv-alert ${alertClass}">
                  <h4>${alert.title}</h4>
                  <p>${alert.message}</p>
                  <small>Vehículo: ${alert.vehicle_name} - Fecha: ${alert.date}</small>
                </div>
              `;
            });

            // Mostrar alertas
            $maintenanceWidget.html(alertsHtml);

            // Mostrar contador de alertas en el menú
            const $menuCounter = $('#gpv-menu-maintenance-count');
            if ($menuCounter.length) {
              $menuCounter.text(response.data.alerts.length).show();
            }
          } else {
            $maintenanceWidget.html(
              '<p>No hay alertas de mantenimiento pendientes.</p>'
            );
          }
        },
        error: function (xhr, status, error) {
          console.error('Error AJAX:', error);
          $maintenanceWidget.html(
            '<p>Error al cargar alertas de mantenimiento.</p>'
          );
        },
      });

      // Marcar alerta como leída
      $(document).on('click', '.gpv-alert-mark-read', function (e) {
        e.preventDefault();

        const $alert = $(this).closest('.gpv-alert');
        const alertId = $(this).data('id');

        $.ajax({
          url: gpvAdminData.ajax_url,
          type: 'POST',
          data: {
            action: 'gpv_mark_alert_read',
            nonce: gpvAdminData.nonce,
            alert_id: alertId,
          },
          success: function (response) {
            if (response.success) {
              $alert.fadeOut(300, function () {
                $(this).remove();

                // Actualizar contador
                const $menuCounter = $('#gpv-menu-maintenance-count');
                if ($menuCounter.length) {
                  const count = parseInt($menuCounter.text()) - 1;
                  if (count > 0) {
                    $menuCounter.text(count);
                  } else {
                    $menuCounter.hide();
                    $maintenanceWidget.html(
                      '<p>No hay alertas de mantenimiento pendientes.</p>'
                    );
                  }
                }
              });
            }
          },
        });
      });
    },

    // Configurar listeners de cambios
    setupChangeListeners: function () {
      // Cálculos automáticos en formulario de movimientos
      if ($('#gpv-movimiento-form').length) {
        const recalculateValues = function () {
          const odometroSalida = parseFloat($('#odometro_salida').val()) || 0;
          const odometroEntrada = parseFloat($('#odometro_entrada').val()) || 0;
          const vehiculoId = $('#vehiculo_id').val();

          if (!vehiculoId) return;

          // Obtener datos del vehículo
          $.ajax({
            url: gpvAdminData.ajax_url,
            type: 'POST',
            data: {
              action: 'gpv_get_vehicle_data',
              nonce: gpvAdminData.nonce,
              vehicle_id: vehiculoId,
            },
            success: function (response) {
              if (response.success) {
                const vehicle = response.data;
                const distancia = odometroEntrada - odometroSalida;

                // Mostrar distancia
                $('#distancia_recorrida').val(distancia.toFixed(2));

                // Calcular combustible consumido
                if (vehicle.factor_consumo > 0) {
                  const combustible = distancia / vehicle.factor_consumo;
                  $('#combustible_consumido').val(combustible.toFixed(2));

                  // Calcular nivel de combustible restante
                  if (
                    vehicle.nivel_combustible >= 0 &&
                    vehicle.capacidad_tanque > 0
                  ) {
                    const consumoPorcentaje =
                      (combustible / vehicle.capacidad_tanque) * 100;
                    const nivelFinal = Math.max(
                      0,
                      vehicle.nivel_combustible - consumoPorcentaje
                    );
                    $('#nivel_combustible').val(nivelFinal.toFixed(2));

                    // Advertencia si el nivel es bajo
                    if (nivelFinal < 20) {
                      $('#nivel_combustible').css('border-color', 'red');
                      if ($('#nivel_warning').length === 0) {
                        $('#nivel_combustible').after(
                          '<span id="nivel_warning" class="gpv-warning">¡Nivel bajo de combustible!</span>'
                        );
                      }
                    } else {
                      $('#nivel_combustible').css('border-color', '');
                      $('#nivel_warning').remove();
                    }
                  }
                }
              }
            },
          });
        };

        // Eventos para recalcular valores
        $('#vehiculo_id').on('change', recalculateValues);
        $('#odometro_salida, #odometro_entrada').on('input', recalculateValues);
      }

      // Formulario de cargas de combustible
      if ($('#gpv-carga-form').length) {
        $('#vehiculo_id').on('change', function () {
          const vehiculoId = $(this).val();
          if (!vehiculoId) return;

          // Obtener datos del vehículo
          $.ajax({
            url: gpvAdminData.ajax_url,
            type: 'POST',
            data: {
              action: 'gpv_get_vehicle_data',
              nonce: gpvAdminData.nonce,
              vehicle_id: vehiculoId,
            },
            success: function (response) {
              if (response.success) {
                const vehicle = response.data;

                // Establecer odómetro actual
                $('#odometro_carga').val(vehicle.odometro_actual);

                // Calcular km desde última carga
                if (vehicle.ultima_carga && vehicle.ultima_carga.odometro) {
                  const kmDesdeUltimaCarga =
                    vehicle.odometro_actual - vehicle.ultima_carga.odometro;
                  $('#km_desde_ultima_carga').val(
                    kmDesdeUltimaCarga.toFixed(2)
                  );
                }

                // Establecer factor de consumo
                $('#factor_consumo').val(vehicle.factor_consumo);
              }
            },
          });
        });

        // Calcular factor de consumo
        $('#calcular_factor').on('click', function (e) {
          e.preventDefault();

          const kmRecorridos =
            parseFloat($('#km_desde_ultima_carga').val()) || 0;
          const litrosCargados = parseFloat($('#litros_cargados').val()) || 0;

          if (kmRecorridos > 0 && litrosCargados > 0) {
            const factorConsumo = kmRecorridos / litrosCargados;
            $('#factor_consumo').val(factorConsumo.toFixed(2));
          } else {
            alert(
              'Ingrese los kilómetros recorridos y litros cargados para calcular el factor de consumo.'
            );
          }
        });
      }
    },
  };

  // Inicializar cuando el documento esté listo
  $(document).ready(function () {
    GPVAdmin.init();
  });
})(jQuery);
