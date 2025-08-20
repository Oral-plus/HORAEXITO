<?php
// Conexión a la base de datos SQL Server
$serverName = "HERCULES"; 
$connectionOptions = [
    "Database" => "CALIDAD",
    "Uid" => "SA",
    "PWD" => "Sky2022*!",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Error en la conexión: " . print_r(sqlsrv_errors(), true));
}

// Capturar las fechas seleccionadas
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

$sql = "SELECT * FROM EFICIENCIA_FABRICADA_DETALLE 
        WHERE fecha BETWEEN ? AND ?
        ORDER BY NOMBRE_PRODUCTO ASC";
$params = [$fechaInicio, $fechaFin];



$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
}
// Procesar resultados
$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}

// Calcular estadísticas generales
$totalEmpleados = count($data);
$promedioGeneral = 0;
$areas = [];
$turnos = [];

if ($totalEmpleados > 0) {
    $sumaTotal = 0;
    foreach ($data as $row) {
        $sumaTotal += $row['TOTAL_PORCENTAJE_FINAL'];
        
        // Recopilar áreas únicas
        if (!in_array($row['AREA_ACTIVIDAD'], $areas)) {
            $areas[] = $row['AREA_ACTIVIDAD'];
        }
        
        // Recopilar turnos únicos
        if (!in_array($row['TURNO'], $turnos)) {
            $turnos[] = $row['TURNO'];
        }
    }
    $promedioGeneral = $sumaTotal / $totalEmpleados;
}

// Calcular estadísticas por área
$areaStats = [];
foreach ($areas as $area) {
    $areaStats[$area] = [
        'count' => 0,
        'total' => 0,
        'average' => 0
    ];
}

foreach ($data as $row) {
    $area = $row['AREA_ACTIVIDAD'];
    $areaStats[$area]['count']++;
    $areaStats[$area]['total'] += $row['TOTAL_PORCENTAJE_FINAL'];
}

foreach ($areaStats as $area => $stats) {
    if ($stats['count'] > 0) {
        $areaStats[$area]['average'] = $stats['total'] / $stats['count'];
    }
}

// ORDENAR ÁREAS POR PROMEDIO DE MAYOR A MENOR
uasort($areaStats, function($a, $b) {
    return $b['average'] <=> $a['average'];
});

// Calcular estadísticas por turno
$turnoStats = [];
foreach ($turnos as $turno) {
    $turnoStats[$turno] = [
        'count' => 0,
        'total' => 0,
        'average' => 0
    ];
}

foreach ($data as $row) {
    $turno = $row['TURNO'];
    $turnoStats[$turno]['count']++;
    $turnoStats[$turno]['total'] += $row['TOTAL_PORCENTAJE_FINAL'];
}

foreach ($turnoStats as $turno => $stats) {
    if ($stats['count'] > 0) {
        $turnoStats[$turno]['average'] = $stats['total'] / $stats['count'];
    }
}

// ORDENAR TURNOS POR PROMEDIO DE MAYOR A MENOR
uasort($turnoStats, function($a, $b) {
    return $b['average'] <=> $a['average'];
});

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Función para determinar el color según el porcentaje
function getPerformanceColor($porcentaje) {
    if ($porcentaje <= 70) {
        return 'bg-red-gradient';
    } elseif ($porcentaje <= 80) {
        return 'bg-yellow-gradient';
    } else {
        return 'bg-green-gradient';
    }
}

// Función para determinar el estado según el porcentaje
function getPerformanceStatus($porcentaje) {
   if ($porcentaje <= 70) {
        return 'Bajo';
    } elseif ($porcentaje <= 80) {
        return 'Regular';
    }  elseif ($porcentaje <= 95) {
        return 'Bueno';
    } else {
        return 'Excelente';
    }
}

// Función para obtener el ranking
function getRanking($index) {
    $rankings = ['🥇', '🥈', '🥉'];
    return isset($rankings[$index]) ? $rankings[$index] : '🏅';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="800">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REFERENCIAS FABRICADAS</title>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #818cf8;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Comfortaa', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e5edff 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1340px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        @media (min-width: 768px) {
            .header {
                flex-direction: row;
            }
        }
        
        .logo-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        @media (min-width: 768px) {
            .logo-title {
                margin-bottom: 0;
            }
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .title {
            display: flex;
            flex-direction: column;
        }
        
        .title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }
        
        .title p {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        
        .current-time {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .reloj {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .controls {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        @media (min-width: 768px) {
            .controls {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .controls {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: 'Comfortaa', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .search-container {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 1;
        }
        
        .search-input {
            padding-left: 35px;
        }
        
        .button {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Comfortaa', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            align-self: flex-end;
        }
        
        .button:hover {
            background-color: #4338ca;
        }
        
        .button-clear {
            background-color: #6b7280;
        }
        
        .button-clear:hover {
            background-color: #4b5563;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (min-width: 768px) {
            .summary-cards {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .summary-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .summary-card-title {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
        }
        
        .summary-card-icon {
            color: #9ca3af;
            font-size: 16px;
        }
        
        .summary-card-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-default {
            background-color: #e5edff;
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: var(--danger-color);
        }
        
        .badge-outline {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f9fafb;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        tr:hover {
            background-color: #f9fafb;
        }
        
        .percentage-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 9999px;
            font-weight: 600;
            text-align: center;
            min-width: 60px;
        }
        
        .bg-red-gradient {
            background: linear-gradient(to right, #ff4d4d, #ff9999);
            color: black;
        }
        
        .bg-yellow-gradient {
            background: linear-gradient(to right, #ffff99, #ffcc00);
            color: black;
        }
        
        .bg-green-gradient {
            background: linear-gradient(to right, #66ff66, #009933);
            color: black;
        }
        
        .area-summary {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        @media (min-width: 640px) {
            .area-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .area-summary {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .area-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            transition: box-shadow 0.2s;
            position: relative;
        }
        
        .area-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .area-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .area-card-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ranking-badge {
            font-size: 20px;
            margin-right: 5px;
        }
        
        .area-card-percentage {
            font-size: 24px;
            font-weight: 700;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .area-card-footer {
            text-align: center;
        }
        
        .abrir-pestaña {
            background: none;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .abrir-pestaña:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .filters-info {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #0c4a6e;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
        
        .videos-section {
            margin-top: 30px;
        }
        
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
        }
        
        .video-container {
            display: none;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .video-container.active {
            display: block;
        }
        
        .video-container h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .video-container video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        /* Estilos especiales para el ranking */
        .top-performer {
            border: 2px solid #ffd700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
        }
        
        .second-performer {
            border: 2px solid #c0c0c0;
            box-shadow: 0 0 10px rgba(192, 192, 192, 0.3);
        }
        
        .third-performer {
            border: 2px solid #cd7f32;
            box-shadow: 0 0 8px rgba(205, 127, 50, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-title">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="title">
                    <h1>REFERENCIAS FABRICADAS</h1>
                   
                </div>
            </div>
            <div class="reloj">
                <?php
                date_default_timezone_set('America/Bogota');
                echo "HORA ACTUAL: " . date("h:i A");
                ?>
            </div>
        </div>
        
        <!-- Controls and Filters -->
        <div class="card">

        
<label for="fechaInicio">Fecha inicio:</label>
<input type="date" id="fechaInicio" value="<?php echo htmlspecialchars($fechaInicio); ?>">

<label for="fechaFin">Fecha fin:</label>
<input  type="date" id="fechaFin" value="<?php echo htmlspecialchars($fechaFin); ?>">




           <form id="dateForm" method="GET" action="" style="display: none;">
    <input type="hidden" name="fecha_inicio" id="hiddenFechaInicio">
    <input type="hidden" name="fecha_fin" id="hiddenFechaFin">
</form>

                
                <div class="form-group search-container">
                    <label for="search" class="form-label">Buscar:</label>
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search" placeholder="Nombre, código, área o turno..." class="form-control search-input">
                </div>
                
              
       
            
            <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                <button type="submit" id="btnConsultar" form="dateForm" class="button">
                    <i class="fas fa-calendar-alt"></i> Consultar Fecha
                </button>

                
                <button type="button" id="clearFilters" class="button button-clear">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </button>

                        <button type="button" class="button button-clear">
    <a href="sebasindex.php" style="text-decoration: none; color: inherit;">
        <i class="fas fa-times"></i> Principal
    </a>
</button>
            </div>
            
            <div id="filtersInfo" class="filters-info" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <span id="filtersText">Filtros activos: </span> 
            </div>

    
        </div>
        
        <!-- Summary Cards -->
       
        <!-- Main Data Table -->
        <div class="card">
        
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ORDEN</th>
                            <th>NOMBRE</th>
                             
                          
                            <th>ÁREA</th>
                            <th>UNIDADES</th>
                           
                           
                        </tr>
                    </thead>
                 <tbody id="employeeTableBody">
    <?php
    // Agrupar sumando TOTAL_UNIDADES por NOMBRE_PRODUCTO y AREA
    $agrupados = [];
    foreach ($data as $row) {
        $clave = $row['NOMBRE_PRODUCTO'] . '|' . $row['AREA_ACTIVIDAD'];

        if (!isset($agrupados[$clave])) {
            $agrupados[$clave] = $row;
        } else {
            $agrupados[$clave]['TOTAL_UNIDADES'] += $row['TOTAL_UNIDADES'];
        }
    }

    foreach ($agrupados as $row):
    ?>
        <tr class="employee-row" 
            data-name="<?php echo htmlspecialchars($row['NOMBRE_PRODUCTO'], ENT_QUOTES); ?>" 
            data-code="<?php echo htmlspecialchars($row['ORDEN'], ENT_QUOTES); ?>" 
            data-area="<?php echo htmlspecialchars($row['AREA_ACTIVIDAD'], ENT_QUOTES); ?>"
            data-turno="<?php echo htmlspecialchars($row['TURNO'], ENT_QUOTES); ?>"
            data-percentage="<?php echo $row['TOTAL_PORCENTAJE_FINAL']; ?>"
            data-status="<?php echo getPerformanceStatus($row['TOTAL_PORCENTAJE_FINAL']); ?>">

            <td><?php echo htmlspecialchars($row['ORDEN'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($row['NOMBRE_PRODUCTO'], ENT_QUOTES); ?></td>
            <td>
                <span class="badge badge-outline"><?php echo htmlspecialchars($row['AREA_ACTIVIDAD'], ENT_QUOTES); ?></span>
            </td>
            <td><?php echo htmlspecialchars($row['TOTAL_UNIDADES'], ENT_QUOTES); ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

                </table>
                <div id="noResults" class="no-results" style="display: none;">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No se encontraron resultados con los filtros aplicados.</p>
                </div>
            </div>
        </div>
        
        <!-- Area Summary - ORDENADO DE MAYOR A MENOR -->
      
        
        <!-- Turno Summary - ORDENADO DE MAYOR A MENOR -->
     
        
        <!-- Videos Section -->
     

            <!-- Contenedores de videos -->
            <div id="container1" class="video-container">
                <h3>🎬 Video 1 - Elementos de Protección</h3>
                <p id="horario-actual1" class="status"></p>
                <video id="video1" width="640" height="360" controls>
                    <source src="VIDEOS/SST.mp4" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>

            <div id="container2" class="video-container">
                <h3>🎬 Video 2 - Orden y Aseo</h3>
                <p id="horario-actual2" class="status"></p>
                <video id="video2" width="640" height="360" controls>
                    <source src="VIDEOS/ORDEN Y ASEO.mp4" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>

            <div id="container3" class="video-container">
                <h3>🎬 Video 3 - Órdenes de Producción</h3>
                <p id="horario-actual3" class="status"></p>
                <video id="video3" width="640" height="360" controls>
                    <source src="VIDEOS/ORDENES DE PRODUCCION.mp4" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>

            <div id="container4" class="video-container">
                <h3>🎬 Video 4 - Pausas Activas</h3>
                <p id="horario-actual4" class="status"></p>
                <video id="video4" width="640" height="360" controls>
                    <source src="VIDEOS/PAUSAS ACTIVAS.mp4" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>

            <div id="container5" class="video-container">
                <h3>🎬 Video 5 - Revisión TVS</h3>
                <p id="horario-actual5" class="status"></p>
                <video id="video5" width="640" height="360" controls>
                    <source src="VIDEOS/REVISION TVS.mp4" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>

            <div id="container6" class="video-container">
                <h3 id="titulo-video6">🎬 Video Motivacional del Día</h3>
                <p id="horario-actual6" class="status"></p>
                <video id="video6" width="640" height="360" controls>
                    <source id="source-video6" src="" type="video/mp4">
                    Tu navegador no soporta el video.
                </video>
            </div>
        </div>
    </div>

    <!-- Hidden form for date submission -->
    <form id="dateForm" method="GET" action="" style="display: none;">
    <input type="hidden" name="fecha" id="hiddenFecha" value="<?php echo $fechaSeleccionada; ?>">
</form>


    <script>
        // Variables globales
        let allRows = [];
        let activeFilters = {
            search: '',
            area: '',
            turno: '',
            status: ''
        };

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todas las filas de empleados
            allRows = Array.from(document.querySelectorAll('#employeeTableBody .employee-row'));
            
            // Configurar event listeners
            setupEventListeners();
            
            // Aplicar filtros iniciales
            applyFilters();
            
            // Inicializar sistema de videos
            inicializarSistemaVideos();
        });

        function setupEventListeners() {
            // Búsqueda en tiempo real
            document.getElementById('search').addEventListener('input', function() {
                activeFilters.search = this.value.toLowerCase();
                applyFilters();
            });

            // Filtro por área
            document.getElementById('filterArea').addEventListener('change', function() {
                activeFilters.area = this.value;
                applyFilters();
            });

            // Filtro por turno
            document.getElementById('filterTurno').addEventListener('change', function() {
                activeFilters.turno = this.value;
                applyFilters();
            });

            // Filtro por estado
            document.getElementById('filterStatus').addEventListener('change', function() {
                activeFilters.status = this.value;
                applyFilters();
            });

            // Limpiar filtros
            document.getElementById('clearFilters').addEventListener('click', function() {
                clearAllFilters();
            });

            // Botones de abrir pestaña
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('abrir-pestaña')) {
                    const url = e.target.getAttribute('data-url');
                    openDetails(url);
                }
            });

            // Actualizar fecha oculta cuando cambie la fecha visible
            document.getElementById('fecha').addEventListener('change', function() {
                document.getElementById('hiddenFecha').value = this.value;
            });
        }

        function applyFilters() {
            let visibleCount = 0;
            let hasActiveFilters = false;

            allRows.forEach(row => {
                const name = row.getAttribute('data-name').toLowerCase();
                const code = row.getAttribute('data-code').toLowerCase();
                const area = row.getAttribute('data-area');
                const turno = row.getAttribute('data-turno');
                const status = row.getAttribute('data-status');

                let showRow = true;

                // Filtro de búsqueda
                if (activeFilters.search) {
                    hasActiveFilters = true;
                    if (!name.includes(activeFilters.search) && 
                        !code.includes(activeFilters.search) && 
                        !area.toLowerCase().includes(activeFilters.search) &&
                        !turno.toLowerCase().includes(activeFilters.search)) {
                        showRow = false;
                    }
                }

                // Filtro por área
                if (activeFilters.area && area !== activeFilters.area) {
                    hasActiveFilters = true;
                    showRow = false;
                }

                // Filtro por turno
                if (activeFilters.turno && turno !== activeFilters.turno) {
                    hasActiveFilters = true;
                    showRow = false;
                }

                // Filtro por estado
                if (activeFilters.status && status !== activeFilters.status) {
                    hasActiveFilters = true;
                    showRow = false;
                }

                // Mostrar u ocultar fila
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Actualizar contador de empleados visibles
            document.getElementById('totalVisible').textContent = visibleCount;
            document.getElementById('totalText').textContent = 
                visibleCount === <?php echo $totalEmpleados; ?> ? 'Activos hoy' : `de ${<?php echo $totalEmpleados; ?>} total`;

            // Mostrar/ocultar mensaje de sin resultados
            const noResults = document.getElementById('noResults');
            if (visibleCount === 0 && hasActiveFilters) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }

            // Actualizar información de filtros
            updateFiltersInfo(hasActiveFilters);
        }

        function updateFiltersInfo(hasActiveFilters) {
            const filtersInfo = document.getElementById('filtersInfo');
            const filtersText = document.getElementById('filtersText');

            if (hasActiveFilters) {
                let activeFiltersText = [];
                
                if (activeFilters.search) {
                    activeFiltersText.push(`Búsqueda: "${activeFilters.search}"`);
                }
                if (activeFilters.area) {
                    activeFiltersText.push(`Área: ${activeFilters.area}`);
                }
                if (activeFilters.turno) {
                    activeFiltersText.push(`Turno: ${activeFilters.turno}`);
                }
                if (activeFilters.status) {
                    activeFiltersText.push(`Estado: ${activeFilters.status}`);
                }
                
                filtersText.textContent = 'Filtros activos: ' + activeFiltersText.join(', ');
                filtersInfo.style.display = 'block';
            } else {
                filtersInfo.style.display = 'none';
            }
        }

        function clearAllFilters() {
            // Limpiar valores de filtros
            document.getElementById('search').value = '';
            document.getElementById('filterArea').value = '';
            document.getElementById('filterTurno').value = '';
            document.getElementById('filterStatus').value = '';

            // Resetear filtros activos
            activeFilters = {
                search: '',
                area: '',
                turno: '',
                status: ''
            };

            // Aplicar filtros (mostrar todo)
            applyFilters();
        }

       



        // Auto-refresh cada 800 segundos
        setTimeout(function() {
            location.reload();
        }, 800000);
    </script>
    <script>
document.getElementById('btnConsultar').addEventListener('click', function() {
    let inicio = document.getElementById('fechaInicio').value;
    let fin = document.getElementById('fechaFin').value;

    if (!inicio || !fin) {
        alert("Debes seleccionar ambas fechas.");
        return;
    }

    document.getElementById('hiddenFechaInicio').value = inicio;
    document.getElementById('hiddenFechaFin').value = fin;
    document.getElementById('dateForm').submit();
});
</script>
</body>
</html>
