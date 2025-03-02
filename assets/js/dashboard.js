/**
 * Driver Dashboard JavaScript
 * Handles interactive features for the driver dashboard
 */
(function ($) {
  // Dashboard module
  const DriverDashboard = {
    // Initialize dashboard functionality
    init: function () {
      this.cacheDOMElements();
      this.bindEvents();
      this.loadDashboardData();
    },

    // Cache DOM elements
    cacheDOMElements: function () {
      this.$dashboard = $('#gpv-driver-dashboard-container');
      this.$newMovementBtn = this.$dashboard.find(
        '[data-action="new-movement"]'
      );
      this.$newFuelLoadBtn = this.$dashboard.find(
        '[data-action="new-fuel-load"]'
      );
      this.$movementModal = $('#gpv-movement-modal');
      this.$fuelLoadModal = $('#gpv-fuel-load-modal');
      this.$movementForm = this.$movementModal.find('form');
      this.$fuelLoadForm = this.$fuelLoadModal.find('form');
    },

    // Bind events
    bindEvents: function () {
      // Open movement modal
      this.$newMovementBtn.on('click', (e) => {
        e.preventDefault();
        this.openMovementModal();
      });

      // Open fuel load modal
      this.$newFuelLoadBtn.on('click', (e) => {
        e.preventDefault();
        this.openFuelLoadModal();
      });

      // Close modal buttons
      this.$dashboard.on('click', '.gpv-modal-close', () => {
        this.closeModals();
      });

      // Submit movement form
      this.$movementForm.on('submit', (e) => {
        e.preventDefault();
        this.submitMovementForm();
      });

      // Submit fuel load form
      this.$fuelLoadForm.on('submit', (e) => {
        e.preventDefault();
        this.submitFuelLoadForm();
      });

      // Vehicle card interactions
      this.$dashboard.on('click', '.gpv-vehicle-card', function () {
        $(this).toggleClass('expanded');
      });
    },

    // Load dashboard data via AJAX
    loadDashboardData: function () {
      $.ajax({
        url: gpvDashboardData.apiUrl + '/driver/dashboard',
        method: 'GET',
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', gpvDashboardData.nonce);
        },
        success: (response) => {
          this.renderDashboardData(response.data);
        },
        error: (xhr, status, error) => {
          this.showErrorMessage(
            'No se pudieron cargar los datos del dashboard.'
          );
        },
      });
    },

    // Render dashboard data
    renderDashboardData: function (data) {
      this.renderVehicles(data.vehicles);
      this.renderMovements(data.movements);
      this.renderFuelLoads(data.fuel_loads);
      this.renderMaintenances(data.maintenances);
      this.updateSummaryStats(data.summary);
    },

    // Render vehicles section
    renderVehicles: function (vehicles) {
      const $vehiclesContainer = this.$dashboard.find('.gpv-vehicles');
      const $vehiclesGrid = $vehiclesContainer.find('.gpv-vehicles-grid');

      // Clear existing vehicles
      $vehiclesGrid.empty();

      if (vehicles.length === 0) {
        $vehiclesGrid.html(
          '<p class="gpv-no-data">No hay vehículos asignados</p>'
        );
        return;
      }

      vehicles.forEach((vehicle) => {
        const vehicleCard = `
                  <div class="gpv-vehicle-card" data-vehicle-id="${vehicle.id}">
                      <div class="gpv-vehicle-header">
                          <h3>${vehicle.siglas} - ${
          vehicle.nombre_vehiculo
        }</h3>
                          <span class="gpv-vehicle-status ${vehicle.estado.toLowerCase()}">
                              ${this.capitalizeFirstLetter(vehicle.estado)}
                          </span>
                      </div>
                      <div class="gpv-vehicle-details">
                          <p>
                              <strong>Odómetro:</strong>
                              ${vehicle.odometro_actual} ${
          vehicle.medida_odometro
        }
                          </p>
                          <p>
                              <strong>Combustible:</strong>
                              <span class="${
                                vehicle.nivel_combustible <= 20
                                  ? 'gpv-low-fuel'
                                  : ''
                              }">
                                  ${vehicle.nivel_combustible.toFixed(2)}%
                              </span>
                          </p>
                      </div>
                  </div>
              `;
        $vehiclesGrid.append(vehicleCard);
      });
    },

    // Render movements section
    renderMovements: function (movements) {
      const $movementsContainer = this.$dashboard.find('.gpv-movements');
      const $movementsTable = $movementsContainer.find('.gpv-data-table tbody');

      // Clear existing movements
      $movementsTable.empty();

      if (movements.length === 0) {
        $movementsTable.html(`
                  <tr>
                      <td colspan="4" class="gpv-no-data">
                          No hay movimientos recientes
                      </td>
                  </tr>
              `);
        return;
      }

      movements.forEach((movement) => {
        const entradaText = movement.hora_entrada
          ? new Date(movement.hora_entrada).toLocaleString()
          : '<span class="gpv-in-progress">En Progreso</span>';

        const movementRow = `
                  <tr>
                      <td>${movement.vehiculo_siglas} - ${
          movement.vehiculo_nombre
        }</td>
                      <td>${new Date(
                        movement.hora_salida
                      ).toLocaleString()}</td>
                      <td>${entradaText}</td>
                      <td>${
                        movement.distancia_recorrida
                          ? movement.distancia_recorrida.toFixed(2) + ' km'
                          : '-'
                      }</td>
                  </tr>
              `;
        $movementsTable.append(movementRow);
      });
    },

    // Render fuel loads section
    renderFuelLoads: function (fuelLoads) {
      const $fuelLoadsContainer = this.$dashboard.find('.gpv-fuel-loads');
      const $fuelLoadsTable = $fuelLoadsContainer.find('.gpv-data-table tbody');

      // Clear existing fuel loads
      $fuelLoadsTable.empty();

      if (fuelLoads.length === 0) {
        $fuelLoadsTable.html(`
                  <tr>
                      <td colspan="4" class="gpv-no-data">
                          No hay cargas de combustible recientes
                      </td>
                  </tr>
              `);
        return;
      }

      fuelLoads.forEach((fuelLoad) => {
        const fuelLoadRow = `
                  <tr>
                      <td>${fuelLoad.vehiculo_siglas} - ${
          fuelLoad.vehiculo_nombre
        }</td>
                      <td>${new Date(
                        fuelLoad.fecha_carga
                      ).toLocaleDateString()}</td>
                      <td>${fuelLoad.litros_cargados.toFixed(2)} L</td>
                      <td>$${(
                        fuelLoad.litros_cargados * fuelLoad.precio
                      ).toFixed(2)}</td>
                  </tr>
              `;
        $fuelLoadsTable.append(fuelLoadRow);
      });
    },

    // Render maintenances section
    renderMaintenances: function (maintenances) {
      const $maintenancesContainer = this.$dashboard.find('.gpv-maintenances');
      const $maintenancesTable = $maintenancesContainer.find(
        '.gpv-data-table tbody'
      );

      // Clear existing maintenances
      $maintenancesTable.empty();

      if (maintenances.length === 0) {
        $maintenancesTable.html(`
                  <tr>
                      <td colspan="4" class="gpv-no-data">
                          No hay mantenimientos próximos
                      </td>
                  </tr>
              `);
        return;
      }

      maintenances.forEach((maintenance) => {
        const maintenanceRow = `
                  <tr>
                      <td>${maintenance.vehiculo_siglas} - ${
          maintenance.vehiculo_nombre
        }</td>
                      <td>${maintenance.tipo}</td>
                      <td>${new Date(
                        maintenance.fecha_programada
                      ).toLocaleDateString()}</td>
                      <td>${this.calculateDaysRemaining(
                        maintenance.fecha_programada
                      )} días</td>
                  </tr>
              `;
        $maintenancesTable.append(maintenanceRow);
      });
    },

    // Update summary statistics
    updateSummaryStats: function (summary) {
      // Example of updating summary stats (adjust selectors as needed)
      $('#total-vehicles').text(summary.total_vehicles);
      $('#total-movements').text(summary.total_movements);
      $('#total-fuel-loads').text(summary.total_fuel_loads);
      $('#total-distance').text(summary.total_distance.toFixed(2) + ' km');
    },

    // Open movement modal
    openMovementModal: function () {
      this.$movementModal.show();
      this.populateVehicleSelect(this.$movementForm.find('#vehiculo_id'));
    },

    // Open fuel load modal
    openFuelLoadModal: function () {
      this.$fuelLoadModal.show();
      this.populateVehicleSelect(this.$fuelLoadForm.find('#vehiculo_id'));
    },

    // Populate vehicle select dropdown
    populateVehicleSelect: function ($select) {
      // Clear existing options
      $select.empty();

      // Add default option
      $select.append(`
              <option value="">Seleccionar Vehículo</option>
          `);

      // Fetch and populate vehicles
      $.ajax({
        url: gpvDashboardData.apiUrl + '/driver/vehicles',
        method: 'GET',
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', gpvDashboardData.nonce);
        },
        success: (response) => {
          response.data.forEach((vehicle) => {
            $select.append(`
                          <option value="${vehicle.id}">
                              ${vehicle.siglas} - ${vehicle.nombre_vehiculo}
                          </option>
                      `);
          });
        },
        error: () => {
          this.showErrorMessage('No se pudieron cargar los vehículos.');
        },
      });
    },

    // Submit movement form
    submitMovementForm: function () {
      $.ajax({
        url: gpvDashboardData.apiUrl + '/driver/movement',
        method: 'POST',
        data: this.$movementForm.serialize(),
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', gpvDashboardData.nonce);
        },
        success: (response) => {
          this.showSuccessMessage('Movimiento registrado correctamente');
          this.closeModals();
          this.loadDashboardData();
        },
        error: (xhr) => {
          const errorMessage = xhr.responseJSON
            ? xhr.responseJSON.message
            : 'Error al registrar el movimiento';
          this.showErrorMessage(errorMessage);
        },
      });
    },

    // Submit fuel load form
    submitFuelLoadForm: function () {
      $.ajax({
        url: gpvDashboardData.apiUrl + '/driver/fuel-load',
        method: 'POST',
        data: this.$fuelLoadForm.serialize(),
        beforeSend: (xhr) => {
          xhr.setRequestHeader('X-WP-Nonce', gpvDashboardData.nonce);
        },
        success: (response) => {
          this.showSuccessMessage(
            'Carga de combustible registrada correctamente'
          );
          this.closeModals();
          this.loadDashboardData();
        },
        error: (xhr) => {
          const errorMessage = xhr.responseJSON
            ? xhr.responseJSON.message
            : 'Error al registrar la carga de combustible';
          this.showErrorMessage(errorMessage);
        },
      });
    },

    // Close all modals
    closeModals: function () {
      this.$movementModal.hide();
      this.$fuelLoadModal.hide();
    },

    // Show success message
    showSuccessMessage: function (message) {
      const $messageContainer = $('<div>', {
        class: 'gpv-alert gpv-alert-success',
        text: message,
      });

      this.$dashboard.prepend($messageContainer);

      // Remove message after 5 seconds
      setTimeout(() => {
        $messageContainer.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    },

    // Show error message
    showErrorMessage: function (message) {
      const $messageContainer = $('<div>', {
        class: 'gpv-alert gpv-alert-error',
        text: message,
      });

      this.$dashboard.prepend($messageContainer);

      // Remove message after 5 seconds
      setTimeout(() => {
        $messageContainer.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    },

    // Utility method to capitalize first letter
    capitalizeFirstLetter: function (string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },

    // Calculate days remaining for maintenance
    calculateDaysRemaining: function (dateString) {
      const maintenanceDate = new Date(dateString);
      const today = new Date();
      const timeDiff = maintenanceDate.getTime() - today.getTime();
      return Math.ceil(timeDiff / (1000 * 3600 * 24));
    },
  };

  // Document ready
  $(document).ready(function () {
    // Initialize dashboard if container exists
    if ($('#gpv-driver-dashboard-container').length) {
      DriverDashboard.init();
    }
  });
})(jQuery);
