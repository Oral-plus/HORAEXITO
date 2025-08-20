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

// Verificar si se recibió el parámetro 'id'
if (isset($_GET['id'])) {
    $codigoOperario = $_GET['id'];
 
    // Consulta a la tabla EFICIENCIA_DETALLE
    $sql = "SELECT * FROM EFICIENCIA_DETALLE WHERE CODIGO_OPERARIO = ?
    AND AREA IN ('ENVASADO', 'PREPARACION','ACONDICIONAMIENTO')";
    
    $params = [$codigoOperario];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
    }

    // Almacenar resultados
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    sqlsrv_free_stmt($stmt);
} else {
    die("No se proporcionó el parámetro 'id'.");
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Operario - LA HORA DEL ÉXITO</title>
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
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .header p {
            font-size: 16px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .operario-info {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--primary-color);
        }
        
        .operario-info h2 {
            color: var(--dark-color);
            font-size: 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .operario-info .codigo {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card .icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .table-container {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .table-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
            border-left: 5px solid var(--primary-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            text-align: center;
            font-size: 13px;
            border: none;
        }
        
        th:first-child {
            border-top-left-radius: 10px;
        }
        
        th:last-child {
            border-top-right-radius: 10px;
        }
        
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            transition: background-color 0.2s;
        }
        
        tr:hover td {
            background-color: #f8fafc;
        }
        
        tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        
        tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }
        
        .percentage-cell {
            font-weight: 600;
            border-radius: 8px;
            padding: 8px;
            color: black !important;
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
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .no-data i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 15px;
        }
        
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--danger-color), #f87171);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .close-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .time-badge {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            th, td {
                padding: 8px 6px;
                font-size: 11px;
            }
            
            .close-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
        
        /* Animaciones */
        .table-container {
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <button class="close-btn" onclick="window.close()" title="Cerrar ventana">
        <i class="fas fa-times"></i>
    </button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <div class="logo">
                    <i class="fas fa-user-circle"></i>
                </div>
                DETALLES DEL OPERARIO
            </h1>
            <p>Información detallada de productividad y eficiencia</p>
        </div>

        <?php if (!empty($data)): ?>
            <!-- Información del Operario -->
         

            <!-- Tabla de Detalles -->
            <div class="table-container">
                <div class="table-title">
                    <i class="fas fa-table"></i> REGISTRO DETALLADO DE ACTIVIDADES
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-clock"></i> H INICIO</th>
                            <th><i class="fas fa-clock"></i> H FINAL</th>
                            <th><i class="fas fa-box"></i> PRODUCTO</th>
                        
                            <th><i class="fas fa-cubes"></i> UNIDADES FABRI</th>
                            <th><i class="fas fa-target"></i> ESPERADAS</th>
                            <th><i class="fas fa-stopwatch"></i> TIEMPO TRABAJ</th>
                              <th><i class="fas fa-stopwatch"></i> TIEMPO MUERTO</th>
                            <th><i class="fas fa-chart-bar"></i> REND. PROD.</th>
                            <th><i class="fas fa-clock"></i> EFIC. TIEMPO</th>
                       <!--     <th><i class="fas fa-percentage"></i> PORCENTAJE</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sumGroup = 0;
                        $counter = 0;
                        foreach ($data as $index => $row): 
                            $porcentaje = (int)$row['PORCENTAJE_TOTAL'] * 0.25;
                            $sumGroup += $porcentaje;
                            $counter++;
                        ?>
                            <tr>
                                <td>
                                    <span class="time-badge">
                                        <?php echo htmlspecialchars($row['INICIO']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="time-badge">
                                        <?php echo htmlspecialchars($row['FINAL']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['NOMBRE_PRODUCTO']); ?></td>
                           
                                <td><strong><?php echo number_format($row['UNIDADES']); ?></strong></td>
                                <td><?php echo number_format($row['ESPERADAS']); ?></td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?php echo htmlspecialchars($row['TIEMPO_TRABJADO_MINU']); ?> min
                                    </span>
                                </td>
                                  <td>
                                    <span class="badge badge-warning">
                                        <?php echo htmlspecialchars($row['TN']); ?> min
                                    </span>
                                </td>
                               <td>
    <span class="percentage-cell <?php echo getPerformanceColor($row['PORCENTAJE1']); ?>">
        <?php echo round($row['PORCENTAJE1']); ?>%
    </span>
</td>

                                <td>
    <span class="percentage-cell <?php echo getPerformanceColor($row['PORCENTAJE2']); ?>">
        <?php echo round($row['PORCENTAJE2']); ?>%
    </span>
</td>

                            
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No se encontraron registros</h3>
                <p>No hay datos disponibles para este operario en las áreas especificadas.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-cerrar después de 5 minutos de inactividad
        let inactivityTimer = setTimeout(() => {
            if (confirm('¿Desea mantener esta ventana abierta?')) {
                // Reiniciar el timer si el usuario quiere mantenerla abierta
                clearTimeout(inactivityTimer);
            } else {
                window.close();
            }
        }, 300000); // 5 minutos

        // Función para imprimir la página
        function printPage() {
            window.print();
        }

        // Agregar evento de teclado para cerrar con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.close();
            }
        });

        // Animación de entrada para las filas de la tabla
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
