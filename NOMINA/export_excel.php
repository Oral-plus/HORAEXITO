<?php
// Configuración de conexión a SQL Server
$serverName = "HERCULES"; // Cambia por tu servidor
$connectionOptions = array(
    "Database" => "calidad",
    "Uid" => "sa",
    "PWD" => "Sky2022*!",
    "CharacterSet" => "UTF-8"
);

// Conectar a SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Obtener parámetros de filtro
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$responsable = isset($_GET['responsable']) ? $_GET['responsable'] : '';

// Construir consulta SQL agrupada
$sql = "SELECT 
            [RESPONSABLE],
            SUM([HORAS]) AS HORAS,
            SUM([RECARGO NOCTURNO DOMINICAL]) AS RECARGO_NOCTURNO_DOMINICAL,
            SUM([RECARGO NOCTURNO]) AS RECARGO_NOCTURNO,
            SUM([HORA EXTRA NOCTURNA]) AS HORA_EXTRA_NOCTURNA,
            SUM([HORA EXTRA DIURNA]) AS HORA_EXTRA_DIURNA,
            SUM([DOMINICAL]) AS DOMINICAL,
            SUM([HORA EXTRA DIURNA DOMINICAL]) AS HORA_EXTRA_DIURNA_DOMINICAL
        FROM [calidad].[dbo].[NOMINA]
        WHERE 1=1";

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
    $sql .= " AND [RESPONSABLE] LIKE ?";
    $params[] = '%' . $responsable . '%';
}

$sql .= " GROUP BY [RESPONSABLE] ORDER BY [RESPONSABLE]";

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Configurar headers para descarga de Excel
$filename = "nomina_agrupada_" . date('Y-m-d_H-i-s') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Crear contenido Excel
echo "<table border='1'>";
echo "<tr style='background-color: #4CAF50; color: white; font-weight: bold;'>";
echo "<th>Responsable</th>";
echo "<th>Horas</th>";
echo "<th>Recargo Nocturno Dominical</th>";
echo "<th>Recargo Nocturno</th>";
echo "<th>Hora Extra Nocturna</th>";
echo "<th>Hora Extra Diurna</th>";
echo "<th>Dominical</th>";
echo "<th>Hora Extra Diurna Dominical</th>";
echo "<th>Total</th>";
echo "</tr>";

$totales = array(
    'HORAS' => 0,
    'RECARGO_NOCTURNO_DOMINICAL' => 0,
    'RECARGO_NOCTURNO' => 0,
    'HORA_EXTRA_NOCTURNA' => 0,
    'HORA_EXTRA_DIURNA' => 0,
    'DOMINICAL' => 0,
    'HORA_EXTRA_DIURNA_DOMINICAL' => 0
);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $total_fila = floatval($row['RECARGO_NOCTURNO_DOMINICAL']) + 
                  floatval($row['RECARGO_NOCTURNO']) + 
                  floatval($row['HORA_EXTRA_NOCTURNA']) + 
                  floatval($row['HORA_EXTRA_DIURNA']) + 
                  floatval($row['DOMINICAL']) + 
                  floatval($row['HORA_EXTRA_DIURNA_DOMINICAL']);
    
    // Acumular totales generales
    $totales['HORAS'] += floatval($row['HORAS']);
    $totales['RECARGO_NOCTURNO_DOMINICAL'] += floatval($row['RECARGO_NOCTURNO_DOMINICAL']);
    $totales['RECARGO_NOCTURNO'] += floatval($row['RECARGO_NOCTURNO']);
    $totales['HORA_EXTRA_NOCTURNA'] += floatval($row['HORA_EXTRA_NOCTURNA']);
    $totales['HORA_EXTRA_DIURNA'] += floatval($row['HORA_EXTRA_DIURNA']);
    $totales['DOMINICAL'] += floatval($row['DOMINICAL']);
    $totales['HORA_EXTRA_DIURNA_DOMINICAL'] += floatval($row['HORA_EXTRA_DIURNA_DOMINICAL']);

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['RESPONSABLE']) . "</td>";
    echo "<td>" . number_format($row['HORAS'], 2) . "</td>";
    echo "<td>" . number_format($row['RECARGO_NOCTURNO_DOMINICAL'], 2) . "</td>";
    echo "<td>" . number_format($row['RECARGO_NOCTURNO'], 2) . "</td>";
    echo "<td>" . number_format($row['HORA_EXTRA_NOCTURNA'], 2) . "</td>";
    echo "<td>" . number_format($row['HORA_EXTRA_DIURNA'], 2) . "</td>";
    echo "<td>" . number_format($row['DOMINICAL'], 2) . "</td>";
    echo "<td>" . number_format($row['HORA_EXTRA_DIURNA_DOMINICAL'], 2) . "</td>";
    echo "<td style='font-weight: bold;'>" . number_format($total_fila, 2) . "</td>";
    echo "</tr>";
}

// Fila de totales generales
$valor_total = $totales['RECARGO_NOCTURNO_DOMINICAL'] + $totales['RECARGO_NOCTURNO'] + 
               $totales['HORA_EXTRA_NOCTURNA'] + $totales['HORA_EXTRA_DIURNA'] + 
               $totales['DOMINICAL'] + $totales['HORA_EXTRA_DIURNA_DOMINICAL'];

echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
echo "<td>TOTALES:</td>";
echo "<td>" . number_format($totales['HORAS'], 2) . "</td>";
echo "<td>" . number_format($totales['RECARGO_NOCTURNO_DOMINICAL'], 2) . "</td>";
echo "<td>" . number_format($totales['RECARGO_NOCTURNO'], 2) . "</td>";
echo "<td>" . number_format($totales['HORA_EXTRA_NOCTURNA'], 2) . "</td>";
echo "<td>" . number_format($totales['HORA_EXTRA_DIURNA'], 2) . "</td>";
echo "<td>" . number_format($totales['DOMINICAL'], 2) . "</td>";
echo "<td>" . number_format($totales['HORA_EXTRA_DIURNA_DOMINICAL'], 2) . "</td>";
echo "<td style='color: #4CAF50;'>" . number_format($valor_total, 2) . "</td>";
echo "</tr>";

echo "</table>";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
