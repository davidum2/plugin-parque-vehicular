/**
 * Script principal para el dashboard de GPV
 */
(function () {
  'use strict';

  // Componentes React para el Dashboard
  const { useState, useEffect, useRef, useCallback } = React;

  /**
   * Componente principal de Dashboard
   */
  const Dashboard = () => {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [dateFilter, setDateFilter] = useState('month');
    const [refreshInterval, setRefreshInterval] = useState(300000); // 5 minutos
    const refreshTimerRef = useRef(null);
    const chartsRef = useRef({});

    // Cargar datos del dashboard
    const loadDashboardData = useCallback(() => {
      setLoading(true);

      fetch(gpvPwaData.root + 'gpv/v1/dashboard', {
        headers: {
          'X-WP-Nonce': gpvPwaData.nonce,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Error obteniendo datos del dashboard');
          }
          return response.json();
        })
        .then((response) => {
          setData(response.data);
          setLoading(false);
        })
        .catch((err) => {
          console.error('Error:', err);
          setError(
            'No se pudieron cargar los datos del dashboard. Por favor, refresca la página.'
          );
          setLoading(false);
        });
    }, []);

    // Configurar intervalo de refresco
    useEffect(() => {
      loadDashboardData();

      if (refreshTimerRef.current) {
        clearInterval(refreshTimerRef.current);
      }

      if (refreshInterval > 0) {
        refreshTimerRef.current = setInterval(
          loadDashboardData,
          refreshInterval
        );
      }

      return () => {
        if (refreshTimerRef.current) {
          clearInterval(refreshTimerRef.current);
        }
      };
    }, [loadDashboardData, refreshInterval]);

    // Manejar cambio en filtro de fecha
    const handleDateFilterChange = (e) => {
      setDateFilter(e.target.value);
      loadDashboardData();
    };

    // Refrescar datos manualmente
    const handleRefresh = () => {
      loadDashboardData();
    };

    // Renderizado condicional
    if (loading && !data) {
      return (
        <div className="gpv-loading">
          <div className="gpv-spinner"></div>
          <p>Cargando datos del dashboard...</p>
        </div>
      );
    }

    if (error) {
      return <div className="gpv-error">{error}</div>;
    }

    return (
      <div className="gpv-dashboard">
        <DashboardHeader
          onRefresh={handleRefresh}
          dateFilter={dateFilter}
          onDateFilterChange={handleDateFilterChange}
        />

        {loading && (
          <div className="gpv-loading-indicator">
            <div className="gpv-spinner-small"></div>
          </div>
        )}

        <VehicleMetrics data={data.vehicles} />
        <MovementMetrics data={data.movements} />
        <FuelMetrics data={data.fuel} />
        <MaintenanceAlerts data={data.maintenance} />

        <DashboardCharts data={data} chartsRef={chartsRef} />

        <RecentMovements data={data.recentMovements} />

        <DashboardFooter lastUpdate={data.last_update} />
      </div>
    );
  };

  /**
   * Componente para la cabecera del dashboard
   */
  const DashboardHeader = ({ onRefresh, dateFilter, onDateFilterChange }) => {
    return (
      <div className="gpv-dashboard-header">
        <div className="gpv-dashboard-title">
          <h1>Dashboard de Flota</h1>
        </div>

        <div className="gpv-dashboard-controls">
          <div className="gpv-dashboard-filters">
            <label className="gpv-filter-label">Período:</label>
            <select
              className="gpv-filter-select"
              value={dateFilter}
              onChange={onDateFilterChange}
            >
              <option value="day">Hoy</option>
              <option value="week">Esta semana</option>
              <option value="month">Este mes</option>
              <option value="year">Este año</option>
            </select>
          </div>

          <button className="gpv-refresh-button" onClick={onRefresh}>
            <svg viewBox="0 0 24 24" width="16" height="16">
              <path
                fill="currentColor"
                d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"
              />
            </svg>
            Refrescar
          </button>
        </div>
      </div>
    );
  };

  /**
   * Componente para métricas de vehículos
   */
  const VehicleMetrics = ({ data }) => {
    if (!data) return null;

    return (
      <section className="gpv-dashboard-metrics">
        <div className="gpv-metric-card">
          <div className="gpv-metric-header">
            <h3 className="gpv-metric-title">Total de Vehículos</h3>
            <div className="gpv-metric-icon vehicles">
              <svg viewBox="0 0 24 24" width="20" height="20">
                <path
                  fill="currentColor"
                  d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"
                />
              </svg>
            </div>
          </div>

          <h2 className="gpv-metric-value">{data.total}</h2>

          <div className="gpv-metric-details">
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Disponibles</span>
              <span className="gpv-detail-value">{data.available}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">En uso</span>
              <span className="gpv-detail-value">{data.in_use}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">En mantenimiento</span>
              <span className="gpv-detail-value">{data.maintenance}</span>
            </div>
          </div>
        </div>
      </section>
    );
  };

  /**
   * Componente para métricas de movimientos
   */
  const MovementMetrics = ({ data }) => {
    if (!data) return null;

    return (
      <section className="gpv-dashboard-metrics">
        <div className="gpv-metric-card">
          <div className="gpv-metric-header">
            <h3 className="gpv-metric-title">Movimientos Hoy</h3>
            <div className="gpv-metric-icon movements">
              <svg viewBox="0 0 24 24" width="20" height="20">
                <path
                  fill="currentColor"
                  d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"
                />
              </svg>
            </div>
          </div>

          <h2 className="gpv-metric-value">{data.today}</h2>

          <div className="gpv-metric-details">
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Este mes</span>
              <span className="gpv-detail-value">{data.month}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Distancia total</span>
              <span className="gpv-detail-value">
                {data.total_distance.toFixed(2)} km
              </span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Movimientos activos</span>
              <span className="gpv-detail-value">{data.active}</span>
            </div>
          </div>
        </div>
      </section>
    );
  };

  /**
   * Componente para métricas de combustible
   */
  const FuelMetrics = ({ data }) => {
    if (!data) return null;

    return (
      <section className="gpv-dashboard-metrics">
        <div className="gpv-metric-card">
          <div className="gpv-metric-header">
            <h3 className="gpv-metric-title">Consumo de Combustible</h3>
            <div className="gpv-metric-icon fuel">
              <svg viewBox="0 0 24 24" width="20" height="20">
                <path
                  fill="currentColor"
                  d="M19.77 7.23l.01-.01-3.72-3.72L15 4.56l2.11 2.11c-.94.36-1.61 1.26-1.61 2.33 0 1.38 1.12 2.5 2.5 2.5.36 0 .69-.08 1-.21v7.21c0 .55-.45 1-1 1s-1-.45-1-1V14c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v16h10v-7.5h1.5v5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V9c0-.69-.28-1.32-.73-1.77zM12 10H6V5h6v5zm6 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"
                />
              </svg>
            </div>
          </div>

          <h2 className="gpv-metric-value">
            {data.month_consumption.toFixed(2)} L
          </h2>

          <div className="gpv-metric-details">
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Consumo promedio</span>
              <span className="gpv-detail-value">
                {data.average_consumption.toFixed(2)} km/L
              </span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Costo mensual</span>
              <span className="gpv-detail-value">
                ${data.month_cost ? data.month_cost.toFixed(2) : '0.00'}
              </span>
            </div>
          </div>
        </div>
      </section>
    );
  };

  /**
   * Componente para alertas de mantenimiento
   */
  const MaintenanceAlerts = ({ data }) => {
    if (!data) return null;

    return (
      <section className="gpv-alerts-section">
        <div className="gpv-alerts-container">
          <div className="gpv-alerts-header">
            <h3 className="gpv-alerts-title">Mantenimientos</h3>
          </div>

          <div className="gpv-metric-details">
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Pendientes</span>
              <span className="gpv-detail-value">{data.pending}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Próximos (7 días)</span>
              <span className="gpv-detail-value">{data.upcoming}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Completados (este mes)</span>
              <span className="gpv-detail-value">{data.completed || 0}</span>
            </div>
            <div className="gpv-detail-item">
              <span className="gpv-detail-label">Costo mensual</span>
              <span className="gpv-detail-value">
                ${data.month_cost ? data.month_cost.toFixed(2) : '0.00'}
              </span>
            </div>
          </div>

          {data.alerts && data.alerts.length > 0 ? (
            <div className="gpv-alerts-list">
              {data.alerts.map((alert, index) => (
                <div key={index} className={`gpv-alert ${alert.priority}`}>
                  <div className="gpv-alert-icon">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                      <path
                        fill="currentColor"
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"
                      />
                    </svg>
                  </div>
                  <div className="gpv-alert-content">
                    <h4 className="gpv-alert-title">{alert.title}</h4>
                    <p className="gpv-alert-message">{alert.message}</p>
                    <div className="gpv-alert-actions">
                      <button className="gpv-alert-action" data-id={alert.id}>
                        Marcar como leído
                      </button>
                      <button
                        className="gpv-alert-action"
                        data-vehicle-id={alert.vehicle_id}
                      >
                        Ver detalles
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : null}
        </div>
      </section>
    );
  };

  /**
   * Componente para gráficos del dashboard
   */
  const DashboardCharts = ({ data, chartsRef }) => {
    useEffect(() => {
      if (!data || typeof Chart === 'undefined') return;

      // Limpiar gráficos existentes
      Object.values(chartsRef.current).forEach((chart) => {
        if (chart) chart.destroy();
      });

      // Gráfico de vehículos
      const vehiclesCtx = document.getElementById('gpv-vehicles-chart');
      if (vehiclesCtx) {
        chartsRef.current.vehicles = new Chart(vehiclesCtx, {
          type: 'doughnut',
          data: {
            labels: ['Disponibles', 'En uso', 'Mantenimiento'],
            datasets: [
              {
                data: [
                  data.vehicles.available,
                  data.vehicles.in_use,
                  data.vehicles.maintenance,
                ],
                backgroundColor: ['#34A853', '#4285F4', '#FBBC05'],
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
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const total = context.dataset.data.reduce(
                      (a, b) => a + b,
                      0
                    );
                    const percentage = Math.round((value / total) * 100);
                    return `${label}: ${value} (${percentage}%)`;
                  },
                },
              },
            },
          },
        });
      }

      // Si hay datos para los gráficos de movimientos y combustible
      if (data.movements.daily_data && data.fuel.monthly_data) {
        // Gráfico de movimientos
        const movementsCtx = document.getElementById('gpv-movements-chart');
        if (movementsCtx) {
          const labels = data.movements.daily_data.map((item) => item.date);
          const counts = data.movements.daily_data.map((item) => item.count);
          const distances = data.movements.daily_data.map(
            (item) => item.distance
          );

          chartsRef.current.movements = new Chart(movementsCtx, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Movimientos',
                  data: counts,
                  borderColor: '#4285F4',
                  backgroundColor: 'rgba(66, 133, 244, 0.1)',
                  borderWidth: 2,
                  tension: 0.3,
                  yAxisID: 'y',
                },
                {
                  label: 'Distancia (km)',
                  data: distances,
                  borderColor: '#34A853',
                  backgroundColor: 'rgba(52, 168, 83, 0.1)',
                  borderWidth: 2,
                  tension: 0.3,
                  yAxisID: 'y1',
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  type: 'linear',
                  display: true,
                  position: 'left',
                  beginAtZero: true,
                  title: {
                    display: true,
                    text: 'Movimientos',
                  },
                },
                y1: {
                  type: 'linear',
                  display: true,
                  position: 'right',
                  beginAtZero: true,
                  grid: {
                    drawOnChartArea: false,
                  },
                  title: {
                    display: true,
                    text: 'Distancia (km)',
                  },
                },
              },
            },
          });
        }

        // Gráfico de combustible
        const fuelCtx = document.getElementById('gpv-fuel-chart');
        if (fuelCtx) {
          const labels = data.fuel.monthly_data.map((item) => item.month);
          const consumption = data.fuel.monthly_data.map(
            (item) => item.consumption
          );
          const costs = data.fuel.monthly_data.map((item) => item.cost);

          chartsRef.current.fuel = new Chart(fuelCtx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Consumo (L)',
                  data: consumption,
                  backgroundColor: '#FBBC05',
                  yAxisID: 'y',
                },
                {
                  label: 'Costo ($)',
                  data: costs,
                  backgroundColor: '#EA4335',
                  type: 'line',
                  yAxisID: 'y1',
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  type: 'linear',
                  display: true,
                  position: 'left',
                  beginAtZero: true,
                  title: {
                    display: true,
                    text: 'Consumo (L)',
                  },
                },
                y1: {
                  type: 'linear',
                  display: true,
                  position: 'right',
                  beginAtZero: true,
                  grid: {
                    drawOnChartArea: false,
                  },
                  title: {
                    display: true,
                    text: 'Costo ($)',
                  },
                },
              },
            },
          });
        }
      }
    }, [data]);

    return (
      <section className="gpv-dashboard-charts">
        <div className="gpv-chart-container">
          <div className="gpv-chart-header">
            <h3 className="gpv-chart-title">Estado de Vehículos</h3>
          </div>
          <canvas id="gpv-vehicles-chart" className="gpv-chart-canvas"></canvas>
        </div>

        <div className="gpv-chart-container">
          <div className="gpv-chart-header">
            <h3 className="gpv-chart-title">Movimientos Diarios</h3>
          </div>
          <canvas
            id="gpv-movements-chart"
            className="gpv-chart-canvas"
          ></canvas>
        </div>

        <div className="gpv-chart-container">
          <div className="gpv-chart-header">
            <h3 className="gpv-chart-title">Consumo de Combustible</h3>
          </div>
          <canvas id="gpv-fuel-chart" className="gpv-chart-canvas"></canvas>
        </div>
      </section>
    );
  };

  /**
   * Componente para movimientos recientes
   */
  const RecentMovements = ({ data }) => {
    if (!data || !data.length) return null;

    return (
      <section className="gpv-dashboard-table">
        <div className="gpv-summary-table">
          <div className="gpv-table-header">
            <h3 className="gpv-alerts-title">Movimientos Recientes</h3>
          </div>
          <table>
            <thead>
              <tr>
                <th>Vehículo</th>
                <th>Conductor</th>
                <th>Salida</th>
                <th>Entrada</th>
                <th>Distancia</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {data.map((movement, index) => (
                <tr key={index}>
                  <td>
                    <strong>{movement.vehiculo_siglas}</strong>
                    <br />
                    <small>{movement.vehiculo_nombre}</small>
                  </td>
                  <td>{movement.conductor}</td>
                  <td>
                    {new Date(movement.hora_salida).toLocaleString()}
                    <br />
                    <small>{movement.odometro_salida} km</small>
                  </td>
                  <td>
                    {movement.hora_entrada ? (
                      <>
                        {new Date(movement.hora_entrada).toLocaleString()}
                        <br />
                        <small>{movement.odometro_entrada} km</small>
                      </>
                    ) : (
                      'En progreso'
                    )}
                  </td>
                  <td>
                    {movement.distancia_recorrida
                      ? `${movement.distancia_recorrida.toFixed(2)} km`
                      : '-'}
                  </td>
                  <td>
                    <span
                      className={`gpv-status ${
                        movement.estado === 'completado'
                          ? 'available'
                          : 'in-use'
                      }`}
                    >
                      {movement.estado === 'completado'
                        ? 'Completado'
                        : 'En Progreso'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    );
  };

  /**
   * Componente para pie de página del dashboard
   */
  const DashboardFooter = ({ lastUpdate }) => {
    return (
      <footer className="gpv-dashboard-footer">
        <div className="gpv-last-update">
          Última actualización: {new Date(lastUpdate).toLocaleString()}
        </div>
        <div className="gpv-footer-links">
          <a href="#" className="gpv-footer-link">
            Ayuda
          </a>
          <a href="#" className="gpv-footer-link">
            Política de Privacidad
          </a>
          <a href="#" className="gpv-footer-link">
            Términos de Uso
          </a>
        </div>
      </footer>
    );
  };

  // Renderizar el componente principal cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('gpv-app-container');
    if (container && container.dataset.panelType === 'dashboard') {
      ReactDOM.render(<Dashboard />, container);
    }
  });
})();
