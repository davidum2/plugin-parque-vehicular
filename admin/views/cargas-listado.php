<?php
/**
 * Admin view for displaying fuel loads list
 *
 * @package GPV
 * @subpackage Admin/Views
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the fuel loads list in admin
 *
 * @return void
 */
function gpv_listado_cargas() {
    global $wpdb;

    // Table name with prefix
    $tabla = $wpdb->prefix . 'gpv_cargas';

    // Get pagination parameters
    $items_per_page = 20;
    $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    $offset = ( $current_page - 1 ) * $items_per_page;

    // Get total rows for pagination
    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $tabla" );
    $total_pages = ceil( $total_items / $items_per_page );

    // Get search parameter if exists
    $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

    // Build query with search and pagination
    $query = "SELECT * FROM $tabla";

    // Add search condition if search term exists
    if ( ! empty( $search ) ) {
        $query .= $wpdb->prepare(
            " WHERE vehiculo_siglas LIKE %s OR vehiculo_nombre LIKE %s",
            '%' . $wpdb->esc_like( $search ) . '%',
            '%' . $wpdb->esc_like( $search ) . '%'
        );
    }

    // Add order and limit
    $query .= " ORDER BY id DESC LIMIT $offset, $items_per_page";

    // Execute query
    $resultados = $wpdb->get_results( $query );

    // Start output
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Cargas de Combustible', 'gestion-parque-vehicular' ); ?></h1>

        <!-- Add New button (if you implement this functionality) -->
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=gpv_cargas_new' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Añadir Nueva', 'gestion-parque-vehicular' ); ?>
        </a>

        <!-- Search form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="gpv_cargas">
            <p class="search-box">
                <label class="screen-reader-text" for="gpv-search-input">
                    <?php esc_html_e( 'Buscar cargas:', 'gestion-parque-vehicular' ); ?>
                </label>
                <input type="search" id="gpv-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php esc_html_e( 'Buscar', 'gestion-parque-vehicular' ); ?>">
            </p>
        </form>

        <!-- Main table -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Vehículo Siglas', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Vehículo Nombre', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Odómetro Carga', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Litros Cargados', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Precio', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Km desde última carga', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Factor Consumo', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Costo Total', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Fecha', 'gestion-parque-vehicular' ); ?></th>
                    <th><?php esc_html_e( 'Acciones', 'gestion-parque-vehicular' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $resultados ) : ?>
                    <?php foreach ( $resultados as $carga ) : ?>
                        <?php
                        // Calculate total cost if it exists
                        $costo_total = isset( $carga->litros_cargados, $carga->precio )
                            ? number_format( $carga->litros_cargados * $carga->precio, 2 )
                            : 'N/A';

                        // Format date if it exists
                        $fecha_formateada = isset( $carga->fecha_carga )
                            ? date_i18n( get_option( 'date_format' ), strtotime( $carga->fecha_carga ) )
                            : 'N/A';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $carga->id ); ?></td>
                            <td><?php echo esc_html( $carga->vehiculo_siglas ); ?></td>
                            <td><?php echo esc_html( $carga->vehiculo_nombre ); ?></td>
                            <td><?php echo esc_html( $carga->odometro_carga ); ?></td>
                            <td><?php echo esc_html( $carga->litros_cargados ); ?></td>
                            <td><?php echo esc_html( $carga->precio ); ?></td>
                            <td><?php echo esc_html( $carga->km_desde_ultima_carga ); ?></td>
                            <td><?php echo esc_html( $carga->factor_consumo ); ?></td>
                            <td><?php echo esc_html( $costo_total ); ?></td>
                            <td><?php echo esc_html( $fecha_formateada ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gpv_cargas_edit&id=' . $carga->id ) ); ?>" class="button-link">
                                    <?php esc_html_e( 'Editar', 'gestion-parque-vehicular' ); ?>
                                </a> |
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gpv_cargas_delete&id=' . $carga->id . '&_wpnonce=' . wp_create_nonce( 'gpv_delete_carga_' . $carga->id ) ) ); ?>" class="button-link gpv-delete" onclick="return confirm('<?php esc_attr_e( '¿Estás seguro de querer eliminar esta carga?', 'gestion-parque-vehicular' ); ?>')">
                                    <?php esc_html_e( 'Eliminar', 'gestion-parque-vehicular' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="11"><?php esc_html_e( 'No hay cargas registradas.', 'gestion-parque-vehicular' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            /* translators: %d: number of items */
                            esc_html( _n( '%d elemento', '%d elementos', $total_items, 'gestion-parque-vehicular' ) ),
                            esc_html( $total_items )
                        );
                        ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        // First page link
                        if ( $current_page > 1 ) :
                            $first_page_url = add_query_arg( 'paged', 1 );
                            echo '<a class="first-page button" href="' . esc_url( $first_page_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Primera página', 'gestion-parque-vehicular' ) . '</span><span aria-hidden="true">&laquo;</span></a>';
                        endif;

                        // Previous page link
                        if ( $current_page > 1 ) :
                            $prev_page = $current_page - 1;
                            $prev_page_url = add_query_arg( 'paged', $prev_page );
                            echo '<a class="prev-page button" href="' . esc_url( $prev_page_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Página anterior', 'gestion-parque-vehicular' ) . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        endif;

                        // Current page display
                        echo '<span class="paging-input">' . esc_html( $current_page ) . ' ' . esc_html__( 'de', 'gestion-parque-vehicular' ) . ' <span class="total-pages">' . esc_html( $total_pages ) . '</span></span>';

                        // Next page link
                        if ( $current_page < $total_pages ) :
                            $next_page = $current_page + 1;
                            $next_page_url = add_query_arg( 'paged', $next_page );
                            echo '<a class="next-page button" href="' . esc_url( $next_page_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Página siguiente', 'gestion-parque-vehicular' ) . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        endif;

                        // Last page link
                        if ( $current_page < $total_pages ) :
                            $last_page_url = add_query_arg( 'paged', $total_pages );
                            echo '<a class="last-page button" href="' . esc_url( $last_page_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Última página', 'gestion-parque-vehicular' ) . '</span><span aria-hidden="true">&raquo;</span></a>';
                        endif;
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add datepicker to date fields if jQuery UI is available
        if ($.fn.datepicker) {
            $('.gpv-date-picker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }

        // Add sorting functionality for columns
        $('.widefat th').click(function() {
            var index = $(this).index();
            sortTable(index);
        });

        function sortTable(column) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.querySelector('.widefat');
            switching = true;
            dir = "asc";

            while (switching) {
                switching = false;
                rows = table.rows;

                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[column];
                    y = rows[i + 1].getElementsByTagName("TD")[column];

                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    });
    </script>
    <?php
}
