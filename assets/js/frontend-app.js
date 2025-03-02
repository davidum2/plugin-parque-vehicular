/**
 * Aplicación Frontend para GPV PWA
 */
document.addEventListener('DOMContentLoaded', function () {
  // Verificar que estamos en una página del plugin
  const appContainer = document.getElementById('gpv-app-container');
  if (!appContainer) return;

  // Determinar el tipo de panel basado en data attributes
  const panelType = appContainer.dataset.panelType || 'default';

  // Inicializar la aplicación React
  initializeReactApp(panelType, appContainer);
});

/**
 * Inicializar la aplicación React
 */
function initializeReactApp(panelType, container) {
  // Asegurarse de que React y ReactDOM están disponibles
  if (typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
    console.error(
      'React o ReactDOM no están disponibles. Verifica que se han cargado correctamente.'
    );
    return;
  }

  // Componentes principales
  const { useState, useEffect, useRef, useCallback } = React;

  /**
   * Componente principal de la aplicación
   */
  const App = () => {
    const [loading, setLoading] = useState(true);
    const [userData, setUserData] = useState(null);
    const [error, setError] = useState(null);
    const [online, setOnline] = useState(navigator.onLine);

    // Estado de conexión
    useEffect(() => {
      function handleOnline() {
        setOnline(true);
        // Intentar sincronizar datos offline
        if (
          'serviceWorker' in navigator &&
          navigator.serviceWorker.controller
        ) {
          navigator.serviceWorker.ready.then((registration) => {
            if ('sync' in registration) {
              registration.sync.register('gpv-sync');
            }
          });
        }
      }

      function handleOffline() {
        setOnline(false);
      }

      window.addEventListener('online', handleOnline);
      window.addEventListener('offline', handleOffline);

      return () => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
      };
    }, []);

    // Cargar datos de usuario
    useEffect(() => {
      setLoading(true);

      // Obtener datos del usuario actual
      fetch(gpvPwaData.root + 'wp/v2/users/me', {
        headers: {
          'X-WP-Nonce': gpvPwaData.nonce,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Error obteniendo datos de usuario');
          }
          return response.json();
        })
        .then((data) => {
          setUserData(data);
          setLoading(false);
        })
        .catch((err) => {
          console.error('Error:', err);
          setError(
            'No se pudieron cargar los datos. Por favor, refresca la página.'
          );
          setLoading(false);
        });
    }, []);

    // Renderizar panel según tipo
    const renderPanel = () => {
      switch (panelType) {
        case 'driver':
          return <DriverPanel userData={userData} online={online} />;
        case 'consultant':
          return <ConsultantPanel userData={userData} online={online} />;
        case 'dashboard':
          return <Dashboard userData={userData} online={online} />;
        default:
          return <div>Panel no reconocido</div>;
      }
    };

    // Renderizado condicional
    if (loading) {
      return (
        <div className="gpv-loading">
          <div className="gpv-spinner"></div>
          <p>Cargando...</p>
        </div>
      );
    }

    if (error) {
      return <div className="gpv-error">{error}</div>;
    }

    return (
      <div className="gpv-app">
        <Header userData={userData} online={online} />
        {renderPanel()}
        <Footer />
      </div>
    );
  };

  /**
   * Componente de cabecera
   */
  const Header = ({ userData, online }) => {
    return (
      <header className="gpv-header">
        <div className="gpv-header-logo">
          <img
            src={
              gpvPwaData.logoUrl ||
              `${gpvPwaData.pluginUrl}assets/images/logo.png`
            }
            alt="GPV Logo"
          />
        </div>
        <div className="gpv-header-title">
          <h1>Gestión de Parque Vehicular</h1>
        </div>
        <div className="gpv-header-user">
          <span
            className={`gpv-connection-status ${online ? 'online' : 'offline'}`}
          >
            {online ? 'En línea' : 'Fuera de línea'}
          </span>
          <span className="gpv-username">{userData?.name || 'Usuario'}</span>
        </div>
      </header>
    );
  };

  /**
   * Componente de pie de página
   */
  const Footer = () => {
    return (
      <footer className="gpv-footer">
        <p>&copy; {new Date().getFullYear()} Gestión de Parque Vehicular</p>
      </footer>
    );
  };

  /**
   * Panel de Conductor
   */
  const DriverPanel = ({ userData, online }) => {
    const [vehicles, setVehicles] = useState([]);
    const [activeTab, setActiveTab] = useState('movements');
    const [loadingVehicles, setLoadingVehicles] = useState(true);
    const [error, setError] = useState(null);

    // Cargar vehículos asignados
    useEffect(() => {
      setLoadingVehicles(true);

      fetch(gpvPwaData.root + 'gpv/v1/vehicles', {
        headers: {
          'X-WP-Nonce': gpvPwaData.nonce,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Error obteniendo vehículos');
          }
          return response.json();
        })
        .then((data) => {
          setVehicles(data.data || []);
          setLoadingVehicles(false);
        })
        .catch((err) => {
          console.error('Error:', err);
          setError('No se pudieron cargar los vehículos asignados.');
          setLoadingVehicles(false);
        });
    }, []);

    // Renderizar pestañas
    const renderTabContent = () => {
      switch (activeTab) {
        case 'movements':
          return (
            <MovementsTab
              vehicles={vehicles}
              userData={userData}
              online={online}
            />
          );
        case 'fuel':
          return (
            <FuelTab vehicles={vehicles} userData={userData} online={online} />
          );
        default:
          return <div>Pestaña no reconocida</div>;
      }
    };

    // Estado de carga
    if (loadingVehicles) {
      return (
        <div className="gpv-loading">
          <div className="gpv-spinner"></div>
          <p>Cargando vehículos asignados...</p>
        </div>
      );
    }

    // Estado de error
    if (error) {
      return <div className="gpv-error">{error}</div>;
    }

    return (
      <div className="gpv-driver-panel">
        <div className="gpv-tabs">
          <button
            className={`gpv-tab ${activeTab === 'movements' ? 'active' : ''}`}
            onClick={() => setActiveTab('movements')}
          >
            Movimientos
          </button>
          <button
            className={`gpv-tab ${activeTab === 'fuel' ? 'active' : ''}`}
            onClick={() => setActiveTab('fuel')}
          >
            Cargas de Combustible
          </button>
        </div>

        <div className="gpv-tab-content">{renderTabContent()}</div>
      </div>
    );
  };

  /**
   * Pestaña de Movimientos
   */
  const MovementsTab = ({ vehicles, userData, online }) => {
    const [activeMovements, setActiveMovements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedVehicle, setSelectedVehicle] = useState(null);
    const [formMode, setFormMode] = useState(null); // 'departure' o 'arrival'
    const [formData, setFormData] = useState({
      vehiculo_id: '',
      odometro_salida: '',
      hora_salida: '',
      proposito: '',
    });

    // Cargar movimientos activos
    useEffect(() => {
      if (vehicles.length === 0) {
        setLoading(false);
        return;
      }

      setLoading(true);

      fetch(gpvPwaData.root + 'gpv/v1/movements?estado=en_progreso', {
        headers: {
          'X-WP-Nonce': gpvPwaData.nonce,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Error obteniendo movimientos activos');
          }
          return response.json();
        })
        .then((data) => {
          setActiveMovements(data.data || []);
          setLoading(false);
        })
        .catch((err) => {
          console.error('Error:', err);
          setLoading(false);

          // En modo offline, intentar recuperar de IndexedDB
          if (!online) {
            getOfflineMovements().then((offlineData) => {
              if (offlineData && offlineData.length > 0) {
                setActiveMovements(offlineData);
              }
            });
          }
        });
    }, [vehicles, online]);

    // Obtener movimientos guardados offline
    const getOfflineMovements = async () => {
      if (!('indexedDB' in window)) return [];

      return new Promise((resolve, reject) => {
        const request = indexedDB.open('gpv-offline', 1);

        request.onerror = (event) => reject(event.target.error);

        request.onsuccess = (event) => {
          const db = event.target.result;
          if (!db.objectStoreNames.contains('movements')) {
            resolve([]);
            return;
          }

          const tx = db.transaction('movements', 'readonly');
          const store = tx.objectStore('movements');
          const request = store.getAll();

          request.onsuccess = (event) => {
            resolve(event.target.result);
          };

          request.onerror = (event) => {
            reject(event.target.error);
          };
        };

        request.onupgradeneeded = (event) => {
          const db = event.target.result;
          if (!db.objectStoreNames.contains('movements')) {
            db.createObjectStore('movements', { keyPath: 'id' });
          }
        };
      });
    };

    // Manejar cambio en formulario
    const handleInputChange = (e) => {
      const { name, value } = e.target;
      setFormData({
        ...formData,
        [name]: value,
      });
    };

    // Iniciar nuevo movimiento
    const startNewMovement = () => {
      setFormMode('departure');
      setFormData({
        vehiculo_id: '',
        odometro_salida: '',
        hora_salida: formatDateTime(new Date()),
        proposito: '',
      });
    };

    // Registrar llegada
    const registerArrival = (movement) => {
      setFormMode('arrival');
      setSelectedVehicle(vehicles.find((v) => v.id === movement.vehiculo_id));
      setFormData({
        id: movement.id,
        vehiculo_id: movement.vehiculo_id,
        odometro_entrada: '',
        hora_entrada: formatDateTime(new Date()),
        nivel_combustible: '',
      });
    };

    // Formatear fecha y hora
    const formatDateTime = (date) => {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(
        2,
        '0'
      )}-${String(date.getDate()).padStart(2, '0')}T${String(
        date.getHours()
      ).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    };

    // Enviar formulario
    const handleSubmit = (e) => {
      e.preventDefault();

      if (formMode === 'departure') {
        // Validaciones
        if (
          !formData.vehiculo_id ||
          !formData.odometro_salida ||
          !formData.hora_salida
        ) {
          alert('Por favor, completa todos los campos obligatorios.');
          return;
        }

        // Preparar datos
        const movementData = {
          vehiculo_id: parseInt(formData.vehiculo_id),
          odometro_salida: parseFloat(formData.odometro_salida),
          hora_salida: new Date(formData.hora_salida).toISOString(),
          proposito: formData.proposito,
          conductor_id: userData.id,
        };

        // Enviar datos
        if (online) {
          fetch(gpvPwaData.root + 'gpv/v1/movements', {
            method: 'POST',
            headers: {
              'X-WP-Nonce': gpvPwaData.nonce,
              'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(movementData),
          })
            .then((response) => {
              if (!response.ok) {
                throw new Error('Error registrando salida');
              }
              return response.json();
            })
            .then((data) => {
              // Actualizar lista de movimientos
              setActiveMovements([...activeMovements, data.data]);
              setFormMode(null);

              // Mostrar mensaje de éxito
              alert('Salida registrada correctamente');
            })
            .catch((err) => {
              console.error('Error:', err);
              alert(
                'Error al registrar la salida. Por favor, intenta nuevamente.'
              );
            });
        } else {
          // Guardar en IndexedDB para sincronización posterior
          saveMovementOffline({
            ...movementData,
            id: 'offline_' + Date.now(),
            type: 'movement',
            action: 'create',
            status: 'pending',
          }).then(() => {
            // Actualizar lista local
            setActiveMovements([
              ...activeMovements,
              {
                id: 'offline_' + Date.now(),
                vehiculo_id: parseInt(formData.vehiculo_id),
                odometro_salida: parseFloat(formData.odometro_salida),
                hora_salida: new Date(formData.hora_salida).toISOString(),
                proposito: formData.proposito,
                conductor_id: userData.id,
                estado: 'en_progreso',
                _offline: true,
              },
            ]);

            setFormMode(null);
            alert(
              'Salida guardada localmente. Se sincronizará cuando vuelvas a estar en línea.'
            );
          });
        }
      } else if (formMode === 'arrival') {
        // Validaciones
        if (!formData.odometro_entrada || !formData.hora_entrada) {
          alert('Por favor, completa todos los campos obligatorios.');
          return;
        }

        // Verificar que odómetro de entrada sea mayor que el de salida
        const movement = activeMovements.find((m) => m.id === formData.id);
        if (
          parseFloat(formData.odometro_entrada) <=
          parseFloat(movement.odometro_salida)
        ) {
          alert('El odómetro de entrada debe ser mayor que el de salida.');
          return;
        }

        // Preparar datos
        const updateData = {
          odometro_entrada: parseFloat(formData.odometro_entrada),
          hora_entrada: new Date(formData.hora_entrada).toISOString(),
          nivel_combustible: formData.nivel_combustible
            ? parseFloat(formData.nivel_combustible)
            : null,
        };

        // Enviar datos
        if (online) {
          fetch(gpvPwaData.root + `gpv/v1/movements/${formData.id}`, {
            method: 'PUT',
            headers: {
              'X-WP-Nonce': gpvPwaData.nonce,
              'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(updateData),
          })
            .then((response) => {
              if (!response.ok) {
                throw new Error('Error registrando entrada');
              }
              return response.json();
            })
            .then((data) => {
              // Actualizar lista de movimientos
              setActiveMovements(
                activeMovements.filter((m) => m.id !== formData.id)
              );
              setFormMode(null);

              // Mostrar mensaje de éxito
              alert('Entrada registrada correctamente');
            })
            .catch((err) => {
              console.error('Error:', err);
              alert(
                'Error al registrar la entrada. Por favor, intenta nuevamente.'
              );
            });
        } else {
          // Guardar en IndexedDB para sincronización posterior
          saveMovementOffline({
            ...updateData,
            id: 'offline_update_' + Date.now(),
            remote_id: formData.id,
            type: 'movement',
            action: 'update',
            status: 'pending',
          }).then(() => {
            // Actualizar lista local
            setActiveMovements(
              activeMovements.filter((m) => m.id !== formData.id)
            );
            setFormMode(null);
            alert(
              'Entrada guardada localmente. Se sincronizará cuando vuelvas a estar en línea.'
            );
          });
        }
      }
    };

    // Guardar movimiento offline
    const saveMovementOffline = async (data) => {
      if (!('indexedDB' in window)) {
        console.error('IndexedDB no está soportado en este navegador');
        return false;
      }

      return new Promise((resolve, reject) => {
        const request = indexedDB.open('gpv-offline', 1);

        request.onerror = (event) => {
          console.error('Error abriendo IndexedDB:', event.target.error);
          reject(event.target.error);
        };

        request.onsuccess = (event) => {
          const db = event.target.result;
          const tx = db.transaction('offline_queue', 'readwrite');
          const store = tx.objectStore('offline_queue');

          const addRequest = store.add(data);

          addRequest.onsuccess = () => resolve(true);
          addRequest.onerror = (event) => reject(event.target.error);
        };

        request.onupgradeneeded = (event) => {
          const db = event.target.result;
          if (!db.objectStoreNames.contains('offline_queue')) {
            db.createObjectStore('offline_queue', { keyPath: 'id' });
          }
        };
      });
    };

    // Renderizar formulario de salida
    const renderDepartureForm = () => {
      return (
        <form className="gpv-form" onSubmit={handleSubmit}>
          <h3>Registrar Salida</h3>

          <div className="gpv-form-group">
            <label htmlFor="vehiculo_id">Vehículo:</label>
            <select
              id="vehiculo_id"
              name="vehiculo_id"
              value={formData.vehiculo_id}
              onChange={handleInputChange}
              required
            >
              <option value="">Selecciona un vehículo</option>
              {vehicles.map((vehicle) => (
                <option
                  key={vehicle.id}
                  value={vehicle.id}
                  disabled={vehicle.estado !== 'disponible'}
                >
                  {vehicle.siglas} - {vehicle.nombre_vehiculo}{' '}
                  {vehicle.estado !== 'disponible' ? '(No disponible)' : ''}
                </option>
              ))}
            </select>
          </div>

          <div className="gpv-form-group">
            <label htmlFor="odometro_salida">Odómetro de Salida:</label>
            <input
              type="number"
              id="odometro_salida"
              name="odometro_salida"
              step="0.1"
              value={formData.odometro_salida}
              onChange={handleInputChange}
              required
            />
          </div>

          <div className="gpv-form-group">
            <label htmlFor="hora_salida">Fecha y Hora de Salida:</label>
            <input
              type="datetime-local"
              id="hora_salida"
              name="hora_salida"
              value={formData.hora_salida}
              onChange={handleInputChange}
              required
            />
          </div>

          <div className="gpv-form-group">
            <label htmlFor="proposito">Propósito del Viaje:</label>
            <textarea
              id="proposito"
              name="proposito"
              value={formData.proposito}
              onChange={handleInputChange}
            ></textarea>
          </div>

          <div className="gpv-form-actions">
            <button type="submit" className="gpv-button gpv-button-primary">
              Registrar Salida
            </button>
            <button
              type="button"
              className="gpv-button"
              onClick={() => setFormMode(null)}
            >
              Cancelar
            </button>
          </div>
        </form>
      );
    };

    // Renderizar formulario de llegada
    const renderArrivalForm = () => {
      return (
        <form className="gpv-form" onSubmit={handleSubmit}>
          <h3>Registrar Entrada</h3>

          <div className="gpv-form-group">
            <label>Vehículo:</label>
            <div className="gpv-form-static">
              {selectedVehicle
                ? `${selectedVehicle.siglas} - ${selectedVehicle.nombre_vehiculo}`
                : ''}
            </div>
          </div>

          <div className="gpv-form-group">
            <label htmlFor="odometro_entrada">Odómetro de Entrada:</label>
            <input
              type="number"
              id="odometro_entrada"
              name="odometro_entrada"
              step="0.1"
              value={formData.odometro_entrada}
              onChange={handleInputChange}
              required
            />
          </div>

          <div className="gpv-form-group">
            <label htmlFor="hora_entrada">Fecha y Hora de Entrada:</label>
            <input
              type="datetime-local"
              id="hora_entrada"
              name="hora_entrada"
              value={formData.hora_entrada}
              onChange={handleInputChange}
              required
            />
          </div>

          <div className="gpv-form-group">
            <label htmlFor="nivel_combustible">Nivel de Combustible (%):</label>
            <input
              type="number"
              id="nivel_combustible"
              name="nivel_combustible"
              min="0"
              max="100"
              step="1"
              value={formData.nivel_combustible}
              onChange={handleInputChange}
            />
          </div>

          <div className="gpv-form-actions">
            <button type="submit" className="gpv-button gpv-button-primary">
              Registrar Entrada
            </button>
            <button
              type="button"
              className="gpv-button"
              onClick={() => setFormMode(null)}
            >
              Cancelar
            </button>
          </div>
        </form>
      );
    };

    // Estado de carga
    if (loading) {
      return (
        <div className="gpv-loading">
          <div className="gpv-spinner"></div>
          <p>Cargando movimientos...</p>
        </div>
      );
    }

    // Renderizar contenido
    return (
      <div className="gpv-movements-tab">
        {formMode === null ? (
          <>
            <div className="gpv-tab-header">
              <h2>Movimientos de Vehículos</h2>
              <button
                className="gpv-button gpv-button-primary"
                onClick={startNewMovement}
              >
                Registrar Salida
              </button>
            </div>

            <div className="gpv-active-movements">
              <h3>Movimientos Activos</h3>

              {activeMovements.length === 0 ? (
                <p className="gpv-no-data">No hay movimientos activos</p>
              ) : (
                <div className="gpv-cards">
                  {activeMovements.map((movement) => {
                    const vehicle = vehicles.find(
                      (v) => v.id === movement.vehiculo_id
                    );

                    return (
                      <div
                        key={movement.id}
                        className={`gpv-card ${
                          movement._offline ? 'gpv-offline-item' : ''
                        }`}
                      >
                        <div className="gpv-card-header">
                          <h4>
                            {vehicle
                              ? `${vehicle.siglas} - ${vehicle.nombre_vehiculo}`
                              : 'Vehículo'}
                          </h4>
                          {movement._offline && (
                            <span className="gpv-offline-badge">
                              Pendiente de sincronizar
                            </span>
                          )}
                        </div>

                        <div className="gpv-card-body">
                          <p>
                            <strong>Odómetro de Salida:</strong>{' '}
                            {movement.odometro_salida}
                          </p>
                          <p>
                            <strong>Fecha/Hora de Salida:</strong>{' '}
                            {new Date(movement.hora_salida).toLocaleString()}
                          </p>
                          {movement.proposito && (
                            <p>
                              <strong>Propósito:</strong> {movement.proposito}
                            </p>
                          )}
                        </div>

                        <div className="gpv-card-actions">
                          <button
                            className="gpv-button"
                            onClick={() => registerArrival(movement)}
                            disabled={movement._offline}
                          >
                            Registrar Entrada
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </>
        ) : formMode === 'departure' ? (
          renderDepartureForm()
        ) : (
          renderArrivalForm()
        )}
      </div>
    );
  };

  // Renderizar la aplicación en el contenedor
  ReactDOM.render(<App />, container);
}
