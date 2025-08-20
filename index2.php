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

// Consulta a la base de datos - ORDENADA POR REND. PROD. (PORCENTAJE) DE MAYOR A MENOR
$sql = "SELECT * FROM EFICIENCIA_POR_DIA WHERE AREA IN ('ENVASADO', 'PREPARACION','ACONDICIONAMIENTO')
AND (FECHA = FORMAT(GETDATE(), 'yyyy-MM-dd'))
ORDER BY PORCENTAJE DESC";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
}

// Organizar resultados por área
$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[$row['AREA']][] = $row;
}

// Segunda consulta
$sql2 = "SELECT * FROM EFICIENCIA_POR_DIA WHERE (FECHA = FORMAT(GETDATE(), 'yyyy-MM-dd'))
ORDER BY PORCENTAJE DESC";

$stmt2 = sqlsrv_query($conn, $sql2);
if ($stmt2 === false) {
    die("Error al ejecutar la segunda consulta: " . print_r(sqlsrv_errors(), true));
}

$data2 = [];
while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
    $data2[] = $row;
}

sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($stmt2);
sqlsrv_close($conn);

// Funciones auxiliares
function getPerformanceColor($porcentaje) {
    if ($porcentaje <= 70) {
        return 'bg-red-gradient';
    } elseif ($porcentaje <= 80) {
        return 'bg-yellow-gradient';
    } else {
        return 'bg-green-gradient';
    }
}

function getChartColor($index) {
    $colors = [
        '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#6b7280'
    ];
    return $colors[$index % count($colors)];
}

function getPerformanceStatus($porcentaje) {
    if ($porcentaje <= 70) {
        return 'Bajo';
    } elseif ($porcentaje <= 80) {
        return 'Regular';
    } else {
        return 'Excelente';
    }
}

// Preparar datos para el gráfico
$chartData = [];
if (!empty($data2)) {
    $areaCounts = [];
    $areaSums = [];
    
    foreach ($data2 as $row) {
        $area = $row['AREA'];
        if (!isset($areaCounts[$area])) {
            $areaCounts[$area] = 0;
            $areaSums[$area] = 0;
        }
        $areaCounts[$area]++;
    }

    foreach ($data2 as $row) {
        $area = $row['AREA'];
        $totalPorArea = $areaCounts[$area];
        $porcentajeParticipacion = $totalPorArea > 0 ? 100 / $totalPorArea : 0;
        $resultadoParticipacion = ($porcentajeParticipacion * $row['TOTAL_PORCENTAJE_FINAL']) / 100 + 1;
        $areaSums[$area] += $resultadoParticipacion;
    }

    $shownAreas = [];
    foreach ($data2 as $row) {
        $area = $row['AREA'];
        if (in_array($area, $shownAreas)) {
            continue;
        }
        
        $shownAreas[] = $area;
        $totalPorArea = $areaCounts[$area];
        $totalParticipacionArea = $areaSums[$area];
        $totalParticipacionAreaRounded = round($totalParticipacionArea);
        
        if ($totalPorArea > 0 && !empty($area)) {
            $chartData[] = [
                'area' => $area,
                'porcentaje' => $totalParticipacionAreaRounded,
                'operarios' => $totalPorArea
            ];
        }
    }
    
    usort($chartData, function($a, $b) {
        return $b['porcentaje'] - $a['porcentaje'];
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="800">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LA HORA DEL ÉXITO - Dashboard WebOS Inmediato</title>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #818cf8;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            
            --container-padding: 20px;
            --header-padding: 30px;
            --card-padding: 30px;
            --font-size-title: 28px;
            --font-size-subtitle: 16px;
            --font-size-table: 13px;
            --logo-size: 80px;
            --button-size: 35px;
            --chart-height: 350px;
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
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--container-padding);
            width: 100%;
        }
        
        .header {
          display: flex;
    flex-direction: row;
    align-items: baseline;
    justify-content: center;
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    width: 100%;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-content: space-between;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .logo-container {
            width: var(--logo-size);
            height: var(--logo-size);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            flex-shrink: 0;
        }
        
        .logo-container i {
            font-size: calc(var(--logo-size) * 0.375);
            color: white;
        }
        
        .title-section {
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        
        .title-section h1 {
            font-size: var(--font-size-title);
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .title-section p {
            font-size: var(--font-size-subtitle);
            color: #6b7280;
            font-weight: 500;
        }
        
        .reloj {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            margin-top: 15px;
        }
        
        /* Panel de Control de Videos */
        .video-control-panel {
            background: linear-gradient(135deg, #1f2937, #374151);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            display: none;
        }
        
        .video-control-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .auto-start-indicator {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .start-videos-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 20px 40px;
            font-size: 20px;
            font-weight: 700;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
            margin: 10px;
            min-width: 250px;
        }
        
        .start-videos-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .start-videos-btn:disabled {
            background: #6b7280;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .video-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .video-status-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .video-status-card h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #e5e7eb;
        }
        
        .video-status-card .status {
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #d1d5db;
        }
        
        .video-container {
            display: none;
            background-color: white;
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 2px solid #e5e7eb;
        }
        
        .video-container.active {
            display: block;
            animation: slideInUp 0.6s ease-out;
        }
        
        .video-container h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .video-container video {
            max-width: 100%;
            width: 100%;
            max-height: 70vh;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: #000;
        }
        
        .video-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .video-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .video-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .video-btn.danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .video-btn.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        /* Estilos para tablas SIN carrusel */
        .tablas-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .tabla-area {
            background-color: white;
            border-radius: 15px;
            padding: var(--card-padding);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            width: 100%;
            animation: fadeIn 0.6s ease-out;
        }
        
        .area-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
            border-left: 5px solid var(--primary-color);
            word-break: break-word;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
            background-color: white;
        }
        
        th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 8px;
            text-align: center;
            font-size: var(--font-size-table);
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        
        th:first-child {
            border-top-left-radius: 10px;
        }
        
        th:last-child {
            border-top-right-radius: 10px;
        }
        
        td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            font-size: var(--font-size-table);
            transition: background-color 0.2s;
            word-break: break-word;
        }
        
        tr:hover td {
            background-color: #f8fafc;
        }
        
        .percentage-cell {
            font-weight: 600;
            border-radius: 8px;
            padding: 8px;
            color: black !important;
            min-width: 60px;
        }
        
        .bg-red-gradient {
            background: linear-gradient(to right, #ff4d4d, #ff9999);
        }
        
        .bg-yellow-gradient {
            background: linear-gradient(to right, #ffff99, #ffcc00);
        }
        
        .bg-green-gradient {
            background: linear-gradient(to right, #66ff66, #009933);
        }
        
        .abrir-pestaña {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            width: var(--button-size);
            height: var(--button-size);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
            margin: 0 auto;
        }
        
        .abrir-pestaña:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.5);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: var(--success-color);
        }
        
        .ranking-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
            white-space: nowrap;
        }
        
        .ranking-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #92400e;
        }
        
        .ranking-2 {
            background: linear-gradient(135deg, #c0c0c0, #e5e7eb);
            color: #374151;
        }
        
        .ranking-3 {
            background: linear-gradient(135deg, #cd7f32, #d97706);
            color: white;
        }
        
        /* Dashboard de productividad por área */
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .stats-section {
            background-color: white;
            border-radius: 15px;
            padding: var(--card-padding);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        .stats-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .area-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 12px;
            border-left: 5px solid;
            transition: all 0.3s;
            cursor: pointer;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .area-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .area-card.excellent {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-left-color: var(--success-color);
        }
        
        .area-card.good {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-left-color: var(--warning-color);
        }
        
        .area-card.poor {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-left-color: var(--danger-color);
        }
        
        .area-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: 1;
            min-width: 0;
        }
        
        .area-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .area-details {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .area-percentage {
            font-size: 24px;
            font-weight: 700;
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .excellent .area-percentage {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .good .area-percentage {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .poor .area-percentage {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border-top: 4px solid var(--primary-color);
            min-width: 0;
        }
        
        .summary-card .icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .summary-card .label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Estilos del Carrusel */
        .carousel-container {
            width: 100%;
            background-color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 2px solid #e5e7eb;
            min-height: auto;
        }

        .carousel-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .slick-dots {
            bottom: -50px;
        }
        
        .slick-dots li button:before {
            color: var(--primary-color);
            font-size: 12px;
        }
        
        .slick-dots li.slick-active button:before {
            color: var(--primary-color);
        }
        
        .slick-prev, .slick-next {
            z-index: 1000;
            width: 50px;
            height: 50px;
        }
        
        .slick-prev:before, .slick-next:before {
            font-size: 30px;
            color: var(--primary-color);
        }
        
        .slick-prev {
            left: -60px;
        }
        
        .slick-next {
            right: -60px;
        }
        
        .slick-prev:before,
        .slick-next:before {
            font-size: 30px;
            color: var(--primary-color);
            opacity: 0.8;
        }

        .slick-prev:hover:before,
        .slick-next:hover:before {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .slick-prev {
                left: 10px;
            }
            
            .slick-next {
                right: 10px;
            }
        }
        
        /* Animaciones */
        @keyframes slideInUp {
            from {
                 opacity: 0;
                 transform: translateY(30px);
             }
            to {
                 opacity: 1;
                 transform: translateY(0);
             }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive para WebOS TV */
        @media (min-width: 1440px) {
            :root {
                --container-padding: 30px;
                --header-padding: 40px;
                --card-padding: 40px;
                --font-size-title: 32px;
                --font-size-subtitle: 18px;
                --font-size-table: 15px;
                --logo-size: 90px;
                --button-size: 40px;
            }
            
            .main-container {
                max-width: 1600px;
            }
            
            .video-control-panel {
                padding: 40px;
            }
            
            .video-control-title {
                font-size: 28px;
            }
            
            .start-videos-btn {
                padding: 25px 50px;
                font-size: 22px;
                min-width: 300px;
            }
            
            .video-status-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            
            .video-container {
                padding: 40px;
            }
            
            .video-container h3 {
                font-size: 28px;
            }
        }
        
        /* Optimizaciones específicas para WebOS */
        @media screen and (min-width: 1920px) {
            body {
                font-size: 18px;
            }
            
            .video-control-title {
                font-size: 32px;
            }
            
            .start-videos-btn {
                padding: 30px 60px;
                font-size: 24px;
                min-width: 350px;
            }
        }
        
        /* Prevenir zoom en WebOS */
        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Optimizar animaciones para TV */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Mejorar legibilidad en pantallas grandes */
        @media screen and (min-width: 1440px) {
            .container {
                max-width: 1400px;
            }
        }
        
        @media screen and (min-width: 1920px) {
            .container {
                max-width: 1800px;
            }
        }
        
        /* Asegurar que el contenido sea visible en WebOS */
        html,
        body {
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Optimizar rendimiento de gradientes */
        .bg-gradient-to-r,
        .bg-gradient-to-br {
            will-change: transform;
            transform: translateZ(0);
        }

        .slick-track {
            display: flex;
            align-items: stretch;
        }

        /* Estilos para indicadores de días específicos */
        .day-indicator {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }

        .day-indicator.active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .day-indicator.inactive {
            background: #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <div class="title-section">
                    <h1>LA HORA DEL ÉXITO</h1>
                </div>
            </div>
            <div class="reloj">
                <i class="fas fa-clock"></i>
                <?php
                date_default_timezone_set('America/Bogota');
                echo "HORA ACTUAL: " . date("h:i A");
                ?>
            </div>
        </div>

        <!-- Panel de Control de Videos -->
        <div id="videoControlPanel" class="video-control-panel">
            <div class="video-control-title">
                <i class="fas fa-play-circle"></i>
                <span>SISTEMA DE VIDEOS ACTIVO</span>
            </div>
            
            <div class="auto-start-indicator">
                <i class="fas fa-bolt"></i>
                <span>SISTEMA INICIADO AUTOMÁTICAMENTE</span>
            </div>
            
            <button id="stopVideosBtn" class="start-videos-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-stop"></i> DETENER VIDEOS
            </button>
            
            <div class="video-status-grid">
                <div class="video-status-card">
                    <h4><i class="fas fa-child"></i> Video 1 - SST</h4>
                    <div id="status1" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-shield-alt"></i> Video 2 - Seguridad</h4>
                    <div id="status2" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-heart"></i> Video 3 - Motivacional</h4>
                    <div id="status3" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-cogs"></i> Video 4 - Procedimientos</h4>
                    <div id="status4" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-star"></i> Video 5 - Calidad</h4>
                    <div id="status5" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-magic"></i> Video 6 - Especial</h4>
                    <div id="status6" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-tools"></i> Video 7 - Mantenimiento</h4>
                    <div id="status7" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-graduation-cap"></i> Video 8 - Capacitación</h4>
                    <div id="status8" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-leaf"></i> Video 9 - Medio Ambiente</h4>
                    <div id="status9" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-handshake"></i> Video 10 - Trabajo en Equipo <span class="day-indicator" id="day-indicator-10">L-V</span></h4>
                    <div id="status10" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-lightbulb"></i> Video 11 - Innovación <span class="day-indicator" id="day-indicator-11">MAR</span></h4>
                    <div id="status11" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-chart-line"></i> Video 12 - Productividad <span class="day-indicator" id="day-indicator-12">MIE</span></h4>
                    <div id="status12" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-trophy"></i> Video 13 - Excelencia <span class="day-indicator" id="day-indicator-13">JUE</span></h4>
                    <div id="status13" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
                <div class="video-status-card">
                    <h4><i class="fas fa-users-cog"></i> Video 14 - Liderazgo</h4>
                    <div id="status14" class="status">✅ Sistema iniciado automáticamente</div>
                </div>
            </div>
        </div>

        <!-- Contenedores de Videos 1-6 (Originales) -->
        <div id="container1" class="video-container">
            <h3><i class="fas fa-child"></i> SST</h3>
            <p id="horario-actual1" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video1" controls preload="metadata">
                <source src="VIDEOS/SST.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video1')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video1')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video1')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container2" class="video-container">
            <h3><i class="fas fa-shield-alt"></i> Video 2 - Capacitación Seguridad</h3>
            <p id="horario-actual2" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video2" controls preload="metadata">
                <source src="VIDEOS/ORDEN Y ASEO.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video2')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video2')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video2')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container3" class="video-container">
            <h3><i class="fas fa-heart"></i> Video 3 - Motivacional</h3>
            <p id="horario-actual3" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video3" controls preload="metadata">
                <source src="VIDEOS/ORDENES DE PRODUCCION.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video3')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video3')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video3')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container4" class="video-container">
            <h3><i class="fas fa-cogs"></i> Video 4 - Procedimientos</h3>
            <p id="horario-actual4" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video4" controls preload="metadata">
                <source src="VIDEOS/PAUSAS ACTIVAS.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video4')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video4')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video4')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container5" class="video-container">
            <h3><i class="fas fa-star"></i> Video 5 - Calidad</h3>
            <p id="horario-actual5" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video5" controls preload="metadata">
                <source src="VIDEOS/REVISION TVS.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video5')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video5')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video5')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container6" class="video-container">
            <h3 id="titulo-video6"><i class="fas fa-magic"></i> Video 6 - Especial del Día</h3>
            <p id="horario-actual6" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video6" controls preload="metadata">
                <source id="source-video6" src="" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video6')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video6')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video6')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <!-- Nuevos Contenedores de Videos 7-13 -->
        <div id="container7" class="video-container">
            <h3><i class="fas fa-tools"></i> Video 7 - Mantenimiento Preventivo</h3>
            <p id="horario-actual7" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video7" controls preload="metadata">
                <source src="VIDEOS/INICIO DE TURNO.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video7')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video7')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video7')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container8" class="video-container">
            <h3><i class="fas fa-graduation-cap"></i> Video 8 - Capacitación Técnica</h3>
            <p id="horario-actual8" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video8" controls preload="metadata">
                <source src="VIDEOS/FINAL DE TURNO.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video8')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video8')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video8')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container9" class="video-container">
            <h3><i class="fas fa-leaf"></i> Video 9 - Cuidado del Medio Ambiente</h3>
            <p id="horario-actual9" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video9" controls preload="metadata">
                <source src="VIDEOS/HORA EXITO.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video9')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video9')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video9')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container10" class="video-container">
            <h3><i class="fas fa-handshake"></i> Video 10 - Trabajo en Equipo <span class="day-indicator" id="container-day-indicator-10">L-V</span></h3>
            <p id="horario-actual10" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video10" controls preload="metadata">
                <source src="VIDEOS/MISION SKY.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video10')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video10')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video10')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container11" class="video-container">
            <h3><i class="fas fa-lightbulb"></i> Video 11 - Innovación y Mejora Continua <span class="day-indicator" id="container-day-indicator-11">MAR</span></h3>
            <p id="horario-actual11" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video11" controls preload="metadata">
                <source src="VIDEOS/VISION SKY.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video11')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video11')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video11')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container12" class="video-container">
            <h3><i class="fas fa-chart-line"></i> Video 12 - Productividad y Eficiencia <span class="day-indicator" id="container-day-indicator-12">MIE</span></h3>
            <p id="horario-actual12" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video12" controls preload="metadata">
                <source src="VIDEOS/VALORES SKY.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video12')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video12')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video12')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container13" class="video-container">
            <h3><i class="fas fa-trophy"></i> Video 13 - Excelencia Operacional <span class="day-indicator" id="container-day-indicator-13">JUE</span></h3>
            <p id="horario-actual13" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video13" controls preload="metadata">
                <source src="VIDEOS/MARCAS SKY.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video13')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video13')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video13')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>

        <div id="container14" class="video-container">
            <h3><i class="fas fa-users-cog"></i> Video 14 - Liderazgo y Desarrollo Personal</h3>
            <p id="horario-actual14" class="status" style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 8px;"></p>
            <video id="video14" controls preload="metadata">
                <source src="VIDEOS/LIDERAZGO.mp4" type="video/mp4">
                Tu navegador no soporta el video.
            </video>
            <div class="video-controls">
                <button class="video-btn success" onclick="playVideo('video14')">
                    <i class="fas fa-play"></i> Reproducir
                </button>
                <button class="video-btn" onclick="pauseVideo('video14')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button class="video-btn danger" onclick="stopVideo('video14')">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        </div>
    </div>

    <!-- Carrusel de Tablas -->
    <div class="carousel-container">
        <div class="carousel-tablas">
            <?php 
            foreach ($data as $area => $rows):
                if (!empty($rows) && count($rows) > 0):
                    $hasValidData = false;
                    foreach ($rows as $row) {
                        if (!empty($row['RESPONSABLE']) || !empty($row['CODIGO_OPERARIO']) || $row['TOTAL_UNIDADES'] > 0) {
                            $hasValidData = true;
                            break;
                        }
                    }
                    
                    if ($hasValidData):
                        usort($rows, function($a, $b) {
                            return $b['PORCENTAJE'] - $a['PORCENTAJE'];
                        });
            ?>
                <div class="tabla-area-carousel">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-trophy"></i> RANK</th>
                                    <th><i class="fas fa-user"></i> NOMBRE</th>
                                 <!---   <th><i class="fas fa-id-card"></i> C. OPERARIO</th> ---> --->
                            <!---  <th><i class="fas fa-info-circle"></i> DETALLES</th>--->
                                    <th><i class="fas fa-industry"></i> ÁREA</th>
                                    <th><i class="fas fa-cubes"></i> U FABRICADAS</th>
                                    <th><i class="fas fa-target"></i> U ESPERADAS</th>
                                    <th><i class="fas fa-chart-bar"></i> REND. PROD.</th>
                                    <th><i class="fas fa-clock"></i> EFIC. TIEMPO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($rows as $index => $row):
                                    $ranking = $index + 1;
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($ranking <= 3): ?>
                                                <span class="ranking-badge ranking-<?php echo $ranking; ?>">
                                                    <?php
                                                    if ($ranking == 1) echo '<i class="fas fa-crown"></i> #1';
                                                    elseif ($ranking == 2) echo '<i class="fas fa-medal"></i> #2';
                                                    elseif ($ranking == 3) echo '<i class="fas fa-award"></i> #3';
                                                    ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="font-weight: 600; color: #6b7280;">#<?php echo $ranking; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['RESPONSABLE'], ENT_NOQUOTES, 'UTF-8'); ?></td>
                                         <!--   <td><?php echo htmlspecialchars($row['CODIGO_OPERARIO']); ?></td>---->
                                       <!--   <td>
                                            <button class="abrir-pestaña"
                                                 data-url="detalle2.php?id=<?php echo htmlspecialchars($row['CODIGO_OPERARIO']); ?>">
                                                +
                                            </button>
                                        </td>---->
                                        <td>
                                            <span class="badge badge-success"><?php echo htmlspecialchars($row['AREA']); ?></span>
                                        </td>
                                        <td><?php echo number_format($row['TOTAL_UNIDADES']); ?></td>
                                        <td><?php echo number_format($row['ESPERADAS']); ?></td>
                                        <td>
                                            <span class="percentage-cell <?php echo getPerformanceColor($row['PORCENTAJE']); ?>">
                                                <?php echo htmlspecialchars($row['PORCENTAJE']); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="percentage-cell <?php echo getPerformanceColor($row['TIEMPO_DIVIDIDO']); ?>">
                                                <?php echo round($row['TIEMPO_DIVIDIDO']); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
                    endif;
                endif;
            endforeach;
            ?>
            
            <?php
            if (!empty($chartData)):
                $totalAreas = count($chartData);
                $promedioGeneral = array_sum(array_column($chartData, 'porcentaje')) / $totalAreas;
                $totalOperarios = array_sum(array_column($chartData, 'operarios'));
            ?>
            <div class="tabla-area-carousel">
                <div class="summary-stats">
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-industry"></i></div>
                        <div class="value"><?php echo $totalAreas; ?></div>
                        <div class="label">Áreas Activas</div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <div class="value"><?php echo $totalOperarios; ?></div>
                        <div class="label">Total Operarios</div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fas fa-percentage"></i></div>
                        <div class="value"><?php echo number_format($promedioGeneral, 1); ?>%</div>
                        <div class="label">Promedio General</div>
                    </div>
                </div>
                
                <div class="stats-section">
                    <div class="stats-title">
                        <i class="fas fa-trophy"></i>
                        <span>Ranking de Áreas</span>
                    </div>
                    
                    <?php foreach ($chartData as $index => $area):
                        $performance = '';
                        if ($area['porcentaje'] > 80) {
                            $performance = 'excellent';
                        } elseif ($area['porcentaje'] > 70) {
                            $performance = 'good';
                        } else {
                            $performance = 'poor';
                        }
                    ?>
                        <div class="area-card <?php echo $performance; ?>">
                            <div class="area-info">
                                <div class="area-name">
                                    <?php if ($index < 3): ?>
                                        <i class="fas fa-<?php echo $index == 0 ? 'crown' : ($index == 1 ? 'medal' : 'award'); ?>"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($area['area']); ?>
                                </div>
                                <div class="area-details">
                                    <span><i class="fas fa-users"></i> <?php echo $area['operarios']; ?> operarios</span>
                                    <span><i class="fas fa-chart-line"></i> Posición #<?php echo $index + 1; ?></span>
                                </div>
                            </div>
                            <div class="area-percentage">
                                <?php echo $area['porcentaje']; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // ===== SISTEMA DE VIDEOS CON INICIO INMEDIATO - 13 VIDEOS CON DÍAS ESPECÍFICOS =====
        
        // Configuración de videos (ahora con 13 videos y días específicos)
        const videosConfig = [
            {
                id: 'video1',
                containerId: 'container1',
                statusId: 'status1',
                horarioActualId: 'horario-actual1',
                nombre: 'SST',
                horariosProgramados: ['08:30', '15:00', '22:00'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video2',
                containerId: 'container2',
                statusId: 'status2',
                horarioActualId: 'horario-actual2',
                nombre: 'Capacitación Seguridad',
                horariosProgramados: ['11:30', '16:00', '01:00'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video3',
                containerId: 'container3',
                statusId: 'status3',
                horarioActualId: 'horario-actual3',
                nombre: 'Motivacional',
                horariosProgramados: ['07:30', '16:15', '00:00'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video4',
                containerId: 'container4',
                statusId: 'status4',
                horarioActualId: 'horario-actual4',
                nombre: 'Pausas',
                horariosProgramados: ['12:00', '17:00', '03:00'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video5',
                containerId: 'container5',
                statusId: 'status5',
                horarioActualId: 'horario-actual5',
                nombre: 'Calidad',
                horariosProgramados: ['09:30', '17:15', '23:00'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video6',
                containerId: 'container6',
                statusId: 'status6',
                horarioActualId: 'horario-actual6',
                nombre: 'Especial del Día',
                horariosProgramados: [],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6], // Todos los días
                videosRotativos: [
                    {
                        src: "VIDEOS/Roar.mp4",
                        titulo: "Video Motivacional - Guar"
                    },
                    {
                        src: "VIDEOS/Ont.mp4",
                        titulo: "Video Motivacional - Éxito"
                    },
                    {
                        src: "VIDEOS/Shake.mp4",
                        titulo: "Video Motivacional - Seguridad"
                    }
                ]
            },
            {
                id: 'video7',
                containerId: 'container7',
                statusId: 'status7',
                horarioActualId: 'horario-actual7',
                nombre: 'Mantenimiento Preventivo',
                horariosProgramados: ['06:00', '13:30', '21:30'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video8',
                containerId: 'container8',
                statusId: 'status8',
                horarioActualId: 'horario-actual8',
                nombre: 'Capacitación Técnica',
                horariosProgramados: ['13:50', '21:20', '05:20'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video9',
                containerId: 'container9',
                statusId: 'status9',
                horarioActualId: 'horario-actual9',
                nombre: 'Cuidado del Medio Ambiente',
                horariosProgramados: ['07:00', '08:00', '09:00','10:00','11:00','12:10','13:00','14:30','15:30','16:30',
                '17:30','18:30','19:30','20:30','21:30','22:30','23:30','00:30','01:30','02:30','03:30','04:30'],
                horariosReproducidos: [],
                diasPermitidos: [0, 1, 2, 3, 4, 5, 6] // Todos los días
            },
            {
                id: 'video10',
                containerId: 'container10',
                statusId: 'status10',
                horarioActualId: 'horario-actual10',
                nombre: 'Trabajo en Equipo',
                horariosProgramados: ['10:30', '18:00', '02:00'],
                horariosReproducidos: [],
                diasPermitidos: [1, 6] // Solo lunes (1) y viernes (5)
            },
            {
                id: 'video11',
                containerId: 'container11',
                statusId: 'status11',
                horarioActualId: 'horario-actual11',
                nombre: 'Innovación y Mejora Continua',
                horariosProgramados: ['10:30', '18:00', '02:00'],
                horariosReproducidos: [],
                diasPermitidos: [2] // Solo martes (2)
            },
            {
                id: 'video12',
                containerId: 'container12',
                statusId: 'status12',
                horarioActualId: 'horario-actual12',
                nombre: 'Productividad y Eficiencia',
                horariosProgramados: ['10:30', '18:00', '02:00'],
                horariosReproducidos: [],
                diasPermitidos: [3] // Solo miércoles (3)
            },
            {
                id: 'video13',
                containerId: 'container13',
                statusId: 'status13',
                horarioActualId: 'horario-actual13',
                nombre: 'Excelencia Operacional',
                horariosProgramados: ['10:30', '18:00', '02:00'],
                horariosReproducidos: [],
                diasPermitidos: [4] // Solo jueves (4)
            },
            {
                id: 'video14',
                containerId: 'container14',
                statusId: 'status14',
                horarioActualId: 'horario-actual14',
                nombre: 'Liderazgo y Desarrollo Personal',
                horariosProgramados: ['09:00', '15:30', '21:00'],
                horariosReproducidos: [],
                diasPermitidos: [5] // Todos los días
            }
        ];

        let sistemaVideosActivo = false;
        let verificacionVideosInterval;
        let videoActualReproduciendo = null;

        // Función para verificar si un video puede reproducirse hoy
        function puedeReproducirseHoy(config) {
            const hoy = new Date().getDay(); // 0=Domingo, 1=Lunes, 2=Martes, etc.
            return config.diasPermitidos.includes(hoy);
        }

        // Función para obtener el nombre del día
        function obtenerNombreDia(numeroDia) {
            const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            return dias[numeroDia];
        }

        // Función para actualizar indicadores de días
        function actualizarIndicadoresDias() {
            const hoy = new Date().getDay();
            
            // Actualizar indicadores en el panel de control
            const indicadores = [
                { id: 'day-indicator-10', dias: [1, 5], texto: 'L-V' },
                { id: 'day-indicator-11', dias: [2], texto: 'MAR' },
                { id: 'day-indicator-12', dias: [3], texto: 'MIE' },
                { id: 'day-indicator-13', dias: [4], texto: 'JUE' }
            ];

            indicadores.forEach(indicador => {
                const elemento = document.getElementById(indicador.id);
                if (elemento) {
                    elemento.textContent = indicador.texto;
                    if (indicador.dias.includes(hoy)) {
                        elemento.className = 'day-indicator active';
                    } else {
                        elemento.className = 'day-indicator inactive';
                    }
                }
            });

            // Actualizar indicadores en los contenedores
            const indicadoresContainer = [
                { id: 'container-day-indicator-10', dias: [1, 5], texto: 'L-V' },
                { id: 'container-day-indicator-11', dias: [2], texto: 'MAR' },
                { id: 'container-day-indicator-12', dias: [3], texto: 'MIE' },
                { id: 'container-day-indicator-13', dias: [4], texto: 'JUE' }
            ];

            indicadoresContainer.forEach(indicador => {
                const elemento = document.getElementById(indicador.id);
                if (elemento) {
                    elemento.textContent = indicador.texto;
                    if (indicador.dias.includes(hoy)) {
                        elemento.className = 'day-indicator active';
                    } else {
                        elemento.className = 'day-indicator inactive';
                    }
                }
            });
        }

        // Funciones de control manual de videos
        function playVideo(videoId) {
            const video = document.getElementById(videoId);
            if (video) {
                video.play().then(() => {
                    console.log(`Video ${videoId} reproducido manualmente`);
                    videoActualReproduciendo = videoId;
                }).catch(error => {
                    console.error(`Error al reproducir ${videoId}:`, error);
                });
            }
        }

        function pauseVideo(videoId) {
            const video = document.getElementById(videoId);
            if (video) {
                video.pause();
                console.log(`Video ${videoId} pausado`);
            }
        }

        function stopVideo(videoId) {
            const video = document.getElementById(videoId);
            const container = document.getElementById(videoId.replace('video', 'container'));
            
            if (video) {
                video.pause();
                video.currentTime = 0;
                console.log(`Video ${videoId} detenido`);
            }
            
            if (container) {
                container.style.display = 'none';
                container.classList.remove('active');
            }
            
            if (videoActualReproduciendo === videoId) {
                videoActualReproduciendo = null;
            }
        }

        // Obtener video del día para video 6
        function obtenerVideoDelDia() {
            const config = videosConfig.find(c => c.id === 'video6');
            const fecha = new Date();
            const dia = fecha.getDay();
            const indiceVideo = dia % config.videosRotativos.length;
            return config.videosRotativos[indiceVideo];
        }

        // Inicializar sistema de videos
        function inicializarSistemaVideos() {
            console.log('🎬 Inicializando sistema de videos para WebOS (14 videos con días específicos)...');
            
            // Actualizar indicadores de días
            actualizarIndicadoresDias();
            
            // Configurar video 6 según el día
            const videoDelDia = obtenerVideoDelDia();
            const sourceElement = document.getElementById('source-video6');
            const tituloElement = document.getElementById('titulo-video6');
            
            if (sourceElement) {
                sourceElement.src = videoDelDia.src;
            }
            
            if (tituloElement) {
                tituloElement.textContent = `🎬 ${videoDelDia.titulo}`;
            }
            
            // Precargar todos los videos
            videosConfig.forEach(config => {
                const video = document.getElementById(config.id);
                if (video) {
                    video.preload = 'metadata';
                    video.muted = false; // No silenciar para WebOS
                    video.load();
                }
            });
            
            console.log(`✅ Video del día configurado: ${videoDelDia.titulo}`);
            console.log('✅ Sistema de 14 videos con días específicos inicializado');
        }

        // Función principal para iniciar el sistema de videos INMEDIATAMENTE
        function iniciarSistemaVideosInmediato() {
            if (sistemaVideosActivo) {
                console.log('⚠️ Sistema de videos ya está activo');
                return;
            }

            console.log('⚡ Iniciando sistema automático de 14 videos INMEDIATAMENTE...');
            sistemaVideosActivo = true;
            
            const hoy = new Date().getDay();
            const nombreHoy = obtenerNombreDia(hoy);
            
            // Actualizar estados
            videosConfig.forEach(config => {
                const statusElement = document.getElementById(config.statusId);
                if (statusElement) {
                    if (!puedeReproducirseHoy(config)) {
                        // Video no programado para hoy
                        const diasTexto = config.diasPermitidos.map(d => obtenerNombreDia(d)).join(', ');
                        statusElement.textContent = `📅 No programado hoy (${nombreHoy}). Días: ${diasTexto}`;
                        statusElement.style.color = '#6b7280';
                    } else if (config.horariosProgramados.length > 0) {
                        // Video programado para hoy
                        const proximoHorario = config.horariosProgramados[0];
                        statusElement.textContent = `⏰ Próximo: ${proximoHorario} (${nombreHoy})`;
                        statusElement.style.color = '#0066cc';
                    } else if (config.id === 'video6') {
                        statusElement.textContent = '🎬 Listo para reproducir después del Video 4';
                        statusElement.style.color = '#10b981';
                    }
                }
            });
            
            // Iniciar verificación de horarios
            verificacionVideosInterval = setInterval(verificarYReproducirVideos, 10000); // Cada 10 segundos
            
            console.log(`✅ Sistema de 14 videos iniciado automáticamente (INMEDIATO) - Hoy es ${nombreHoy}`);
        }

        // Detener sistema de videos
        function detenerSistemaVideos() {
            console.log('🛑 Deteniendo sistema de 14 videos...');
            sistemaVideosActivo = false;
            
            // Limpiar interval
            if (verificacionVideosInterval) {
                clearInterval(verificacionVideosInterval);
            }
            
            // Detener video actual si está reproduciéndose
            if (videoActualReproduciendo) {
                stopVideo(videoActualReproduciendo);
            }
            
            // Resetear estados
            videosConfig.forEach(config => {
                const statusElement = document.getElementById(config.statusId);
                if (statusElement) {
                    statusElement.textContent = '⏹️ Sistema detenido';
                    statusElement.style.color = '#6b7280';
                }
                
                // Ocultar contenedores
                const container = document.getElementById(config.containerId);
                if (container) {
                    container.style.display = 'none';
                    container.classList.remove('active');
                }
                
                // Resetear horarios reproducidos
                config.horariosReproducidos = [];
            });
            
            console.log('✅ Sistema de 14 videos detenido');
        }

        // Verificar y reproducir videos según horarios y días
        function verificarYReproducirVideos() {
            if (!sistemaVideosActivo) return;
            
            const ahora = new Date();
            const hora = ahora.getHours().toString().padStart(2, '0');
            const minutos = ahora.getMinutes().toString().padStart(2, '0');
            const horaActual = `${hora}:${minutos}`;
            const hoy = ahora.getDay();
            
            console.log(`🕐 Verificando horarios (14 videos)... Hora actual: ${horaActual}, Día: ${obtenerNombreDia(hoy)}`);
            
            videosConfig.forEach(config => {
                // Verificar si el video puede reproducirse hoy
                if (!puedeReproducirseHoy(config)) {
                    return; // Saltar este video si no es su día
                }
                
                if (config.horariosProgramados.length > 0) {
                    config.horariosProgramados.forEach(horarioProgramado => {
                        if (horaActual === horarioProgramado && !config.horariosReproducidos.includes(horarioProgramado)) {
                            console.log(`⏰ Hora de reproducir: ${config.nombre} a las ${horarioProgramado} (${obtenerNombreDia(hoy)})`);
                            reproducirVideoAutomatico(config, horarioProgramado);
                        }
                    });
                }
            });
        }

        // Reproducir video automáticamente
        function reproducirVideoAutomatico(config, horarioProgramado) {
            const video = document.getElementById(config.id);
            const container = document.getElementById(config.containerId);
            const statusElement = document.getElementById(config.statusId);
            const horarioActualElement = document.getElementById(config.horarioActualId);
            
            if (!video || !container || !statusElement) {
                console.error(`❌ Elementos no encontrados para ${config.nombre}`);
                return;
            }
            
            console.log(`🎬 Reproduciendo ${config.nombre}...`);
            
            // Mostrar contenedor
            container.style.display = 'block';
            container.classList.add('active');
            videoActualReproduciendo = config.id;
            
            // Actualizar información
            if (horarioActualElement) {
                const hoy = obtenerNombreDia(new Date().getDay());
                horarioActualElement.textContent = `🕐 Reproduciendo horario programado: ${horarioProgramado} (${hoy})`;
            }
            
            // Preparar video
            video.currentTime = 0;
            video.muted = false;
            
            // Intentar reproducir
            video.play().then(() => {
                config.horariosReproducidos.push(horarioProgramado);
                statusElement.textContent = `▶️ Reproduciendo: ${config.nombre}`;
                statusElement.style.color = '#10b981';
                
                console.log(`✅ ${config.nombre} reproducido exitosamente`);
                
                // Manejar fin del video
                video.addEventListener('ended', () => {
                    console.log(`🏁 ${config.nombre} terminó de reproducirse`);
                    
                    // Si es el video 4, reproducir video 6
                    if (config.id === 'video4') {
                        console.log('🎬 Video 4 terminó, reproduciendo Video 6...');
                        setTimeout(() => {
                            reproducirVideo6Automatico();
                        }, 2000);
                    }
                    
                    // Ocultar contenedor después de un tiempo
                    setTimeout(() => {
                        container.style.display = 'none';
                        container.classList.remove('active');
                        videoActualReproduciendo = null;
                        
                        // Actualizar estado
                        const proximoHorario = config.horariosProgramados.find(
                            horario => !config.horariosReproducidos.includes(horario)
                        );
                        
                        if (proximoHorario) {
                            const hoy = obtenerNombreDia(new Date().getDay());
                            statusElement.textContent = `⏰ Próximo: ${proximoHorario} (${hoy})`;
                            statusElement.style.color = '#0066cc';
                        } else {
                            statusElement.textContent = '🏁 Todos los horarios completados';
                            statusElement.style.color = '#6b7280';
                        }
                    }, 3000);
                }, { once: true });
                
            }).catch(error => {
                console.error(`❌ Error al reproducir ${config.nombre}:`, error);
                statusElement.textContent = `❌ Error: ${config.nombre}`;
                statusElement.style.color = '#ef4444';
                
                // Marcar como reproducido para evitar intentos repetidos
                config.horariosReproducidos.push(horarioProgramado);
                
                // Ocultar contenedor
                setTimeout(() => {
                    container.style.display = 'none';
                    container.classList.remove('active');
                    videoActualReproduciendo = null;
                }, 3000);
            });
        }

        // Reproducir video 6 automáticamente
        function reproducirVideo6Automatico() {
            const config = videosConfig.find(c => c.id === 'video6');
            const video = document.getElementById('video6');
            const container = document.getElementById('container6');
            const statusElement = document.getElementById('status6');
            const horarioActualElement = document.getElementById('horario-actual6');
            
            if (!video || !container || !statusElement) {
                console.error('❌ Elementos del Video 6 no encontrados');
                return;
            }
            
            const videoDelDia = obtenerVideoDelDia();
            console.log(`🎬 Reproduciendo Video 6: ${videoDelDia.titulo}`);
            
            // Mostrar contenedor
            container.style.display = 'block';
            container.classList.add('active');
            videoActualReproduciendo = 'video6';
            
            // Actualizar información
            if (horarioActualElement) {
                const diaSemana = obtenerNombreDia(new Date().getDay());
                horarioActualElement.textContent = `🎬 Reproducción automática después del Video 4 (${diaSemana})`;
            }
            
            // Preparar video
            video.currentTime = 0;
            video.muted = false;
            
            // Intentar reproducir
            video.play().then(() => {
                statusElement.textContent = `▶️ Reproduciendo: ${videoDelDia.titulo}`;
                statusElement.style.color = '#10b981';
                
                console.log(`✅ Video 6 (${videoDelDia.titulo}) reproducido exitosamente`);
                
                // Manejar fin del video
                video.addEventListener('ended', () => {
                    console.log(`🏁 Video 6 (${videoDelDia.titulo}) terminó`);
                    
                    setTimeout(() => {
                        container.style.display = 'none';
                        container.classList.remove('active');
                        videoActualReproduciendo = null;
                        statusElement.textContent = `✅ Completado: ${videoDelDia.titulo}`;
                        statusElement.style.color = '#6b7280';
                    }, 3000);
                }, { once: true });
                
            }).catch(error => {
                console.error(`❌ Error al reproducir Video 6:`, error);
                statusElement.textContent = `❌ Error: ${videoDelDia.titulo}`;
                statusElement.style.color = '#ef4444';
                
                setTimeout(() => {
                    container.style.display = 'none';
                    container.classList.remove('active');
                    videoActualReproduciendo = null;
                }, 3000);
            });
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            console.log('⚡ Dashboard WebOS con INICIO INMEDIATO cargado (14 videos con días específicos)');
            
            // Inicializar sistema
            inicializarSistemaVideos();
            
            // ⚡ INICIO INMEDIATO - Se ejecuta automáticamente al cargar
            setTimeout(() => {
                console.log('⚡ Ejecutando INICIO INMEDIATO del sistema de 14 videos con días específicos...');
                iniciarSistemaVideosInmediato();
            }, 1000); // Solo 1 segundo de delay para mostrar la interfaz
            
            // Botón detener videos
            document.getElementById('stopVideosBtn').addEventListener('click', detenerSistemaVideos);
            
            // Botones de abrir pestaña
            const botones = document.querySelectorAll('.abrir-pestaña');
            botones.forEach(boton => {
                boton.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    const width = 1200;
                    const height = 400;
                    const screenWidth = window.screen.width;
                    const screenHeight = window.screen.height;
                    const left = (screenWidth - width) / 2;
                    const top = (screenHeight - height) / 2;

                    window.open(
                        url,
                        '_blank',
                        `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,menubar=no,status=no`
                    );
                });
            });
            
            // Inicializar carrusel
            $('.carousel-tablas').slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 5000,  // 5 segundos
                pauseOnHover: true,
                pauseOnFocus: true,
                arrows: true,
                fade: false,
                cssEase: 'ease-in-out',
                adaptiveHeight: true,
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            arrows: false,
                            dots: true,
                            adaptiveHeight: true
                        }
                    }
                ]
            });

            console.log('✅ Carrusel inicializado correctamente');
            console.log('✅ Sistema con INICIO INMEDIATO de 14 videos con días específicos inicializado correctamente');
        });
    </script>
</body>
</html>
