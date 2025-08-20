<?php
// Configuración de conexión a SQL Server
$serverName = "HERCULES"; // Cambia por tu servidor
$database = "calidad";
$username = "sa";
$password = "Sky2022*!";

$connectionInfo = array(
    "Database" => $database,
    "UID" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
);

// Variables para filtros
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : date('Y-m-d');
$responsable = isset($_POST['responsable']) ? $_POST['responsable'] : '';

$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Obtener lista de responsables para el filtro
$sql_responsables = "SELECT DISTINCT RESPONSABLE FROM [calidad].[dbo].[NOMINA] ORDER BY RESPONSABLE";
$stmt_responsables = sqlsrv_query($conn, $sql_responsables);
$responsables = array();
if ($stmt_responsables) {
    while ($row = sqlsrv_fetch_array($stmt_responsables, SQLSRV_FETCH_ASSOC)) {
        $responsables[] = $row['RESPONSABLE'];
    }
}

// Construir consulta con filtros
$sql = "SELECT [RESPONSABLE], [FECHA], [CODIGO_OPERARIO], [HORAS], [TURNO], [FECHA_AJUSTADA], 
        [RECARGO NOCTURNO DOMINICAL], [RECARGO NOCTURNO], [HORA EXTRA NOCTURNA], 
        [HORA EXTRA DIURNA], [DOMINICAL], [HORA EXTRA DIURNA DOMINICAL]
        FROM [calidad].[dbo].[NOMINA] WHERE 1=1";

$params = array();

if (!empty($fecha_desde)) {
    $sql .= " AND [FECHA_AJUSTADA] >= ?";
    $params[] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $sql .= " AND [FECHA_AJUSTADA] <= ?";
    $params[] = $fecha_hasta;
}

if (!empty($responsable)) {
    $sql .= " AND [RESPONSABLE] = ?";
    $params[] = $responsable;
}

$sql .= " ORDER BY [FECHA_AJUSTADA] DESC, [RESPONSABLE]";

$stmt = sqlsrv_query($conn, $sql, $params);

$datos = array();
$totales = array(
    'registros' => 0,
    'recargo_nocturno_dominical' => 0,
    'recargo_nocturno' => 0,
    'hora_extra_nocturna' => 0,
    'hora_extra_diurna' => 0,
    'dominical' => 0,
    'hora_extra_diurna_dominical' => 0,
    'total_horas' => 0
);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $datos[] = $row;
        $totales['registros']++;
        $totales['recargo_nocturno_dominical'] += floatval($row['RECARGO NOCTURNO DOMINICAL']);
        $totales['recargo_nocturno'] += floatval($row['RECARGO NOCTURNO']);
        $totales['hora_extra_nocturna'] += floatval($row['HORA EXTRA NOCTURNA']);
        $totales['hora_extra_diurna'] += floatval($row['HORA EXTRA DIURNA']);
        $totales['dominical'] += floatval($row['DOMINICAL']);
        $totales['hora_extra_diurna_dominical'] += floatval($row['HORA EXTRA DIURNA DOMINICAL']);
        
        $total_fila = floatval($row['RECARGO NOCTURNO DOMINICAL']) + 
                     floatval($row['RECARGO NOCTURNO']) + 
                     floatval($row['HORA EXTRA NOCTURNA']) + 
                     floatval($row['HORA EXTRA DIURNA']) + 
                     floatval($row['DOMINICAL']) + 
                     floatval($row['HORA EXTRA DIURNA DOMINICAL']);
        $totales['total_horas'] += $total_fila;
    }
}

$promedio = $totales['registros'] > 0 ? $totales['total_horas'] / $totales['registros'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Nómina</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-icon">📊</div>
            <h1>CONSULTA DE NÓMINA</h1>
        </header>

        <div class="filters-section">
            <form method="POST" class="filters-form">
                <div class="date-filters">
                    <div class="filter-group">
                        <label>📅 Fecha Inicio</label>
                        <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" class="date-input">
                    </div>
                    <div class="filter-group">
                        <label>📅 Fecha Fin</label>
                        <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" class="date-input">
                    </div>
                </div>
                
                <div class="responsable-filter">
                    <label>👤 Responsable</label>
                    <select name="responsable" class="responsable-select">
                        <option value="">Todos los responsables</option>
                        <?php foreach ($responsables as $resp): ?>
                            <option value="<?php echo htmlspecialchars($resp); ?>" 
                                    <?php echo ($responsable == $resp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($resp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn-consultar">🔍 Consultar</button>
                    <button type="button" class="btn-exportar" onclick="exportarExcel()">📊 Exportar a Excel</button>
                </div>
            </form>

            <div class="date-info">
                <span>🕒 Hoy: <?php echo date('d/m/Y'); ?></span>
                <small>Cualquier rango disponible</small>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-title">TOTAL REGISTROS</div>
                <div class="card-value total-registros"><?php echo number_format($totales['registros']); ?></div>
            </div>
            <div class="summary-card">
                <div class="card-title">TOTAL HORAS</div>
                <div class="card-value total-horas"><?php echo number_format($totales['total_horas'], 2); ?></div>
            </div>
            <div class="summary-card">
                <div class="card-title">PROMEDIO</div>
                <div class="card-value promedio"><?php echo number_format($promedio, 2); ?></div>
            </div>
        </div>

        <div class="details-section">
            <div class="section-header">
                <h2>📋 Detalle de Registros</h2>
                <p>📅 Desde: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> hasta: <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?></p>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha Ajustada</th>
                            <th>Día</th>
                            <th>Código Operario</th>
                            <th>Responsable</th>
                            <th>Turno</th>
                            <th>Horas Trabajadas</th>
                            <th>Recargo Nocturno Dom.</th>
                            <th>Recargo Nocturno</th>
                            <th>Hora Extra Nocturna</th>
                            <th>Hora Extra Diurna</th>
                            <th>Dominical</th>
                            <th>Hora Extra Diurna Dom.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($datos)): ?>
                            <tr>
                                <td colspan="12" class="no-data">No se encontraron registros para los filtros seleccionados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($datos as $fila): ?>
                                <?php 
                                $fechaAjustada = $fila['FECHA_AJUSTADA'] ? $fila['FECHA_AJUSTADA']->format('Y-m-d') : null;
                                $diaSemana = '';
                                if ($fechaAjustada) {
                                    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                                    $numeroDia = date('w', strtotime($fechaAjustada));
                                    $diaSemana = $dias[$numeroDia];
                                }
                                ?>
                                <tr>
                                    <td><?php echo $fila['FECHA_AJUSTADA'] ? $fila['FECHA_AJUSTADA']->format('d/m/Y') : ''; ?></td>
                                    <td><?php echo $diaSemana; ?></td>
                                    <td><?php echo htmlspecialchars($fila['CODIGO_OPERARIO']); ?></td>
                                    <td class="responsable-cell"><?php echo htmlspecialchars($fila['RESPONSABLE']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['TURNO']); ?></td>
                                    <td><?php echo number_format($fila['HORAS'], 2); ?></td>
                                    <td><?php echo number_format($fila['RECARGO NOCTURNO DOMINICAL'], 2); ?></td>
                                    <td><?php echo number_format($fila['RECARGO NOCTURNO'], 2); ?></td>
                                    <td><?php echo number_format($fila['HORA EXTRA NOCTURNA'], 2); ?></td>
                                    <td><?php echo number_format($fila['HORA EXTRA DIURNA'], 2); ?></td>
                                    <td><?php echo number_format($fila['DOMINICAL'], 2); ?></td>
                                    <td><?php echo number_format($fila['HORA EXTRA DIURNA DOMINICAL'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function exportarExcel() {
            const fechaDesde = document.querySelector('input[name="fecha_desde"]').value;
            const fechaHasta = document.querySelector('input[name="fecha_hasta"]').value;
            const responsable = document.querySelector('select[name="responsable"]').value;

            const url = `export_excel.php?fecha_desde=${encodeURIComponent(fechaDesde)}&fecha_hasta=${encodeURIComponent(fechaHasta)}&responsable=${encodeURIComponent(responsable)}`;
            window.location.href = url;
        }
    </script>
</body>
</html>

<?php
sqlsrv_close($conn);
?>
