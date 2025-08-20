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

// Verificar si se recibieron los parámetros necesarios
if (isset($_GET['id'])) {
    $codigoOperario = $_GET['id'];
    
    // Capturar la fecha (si no se proporciona, usar la fecha actual)
    $fechaSeleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
    
    // Verificar el formato correcto de la fecha
    if (DateTime::createFromFormat('Y-m-d', $fechaSeleccionada) === false) {
        die("Fecha no válida");
    }
 
    // Consulta a la tabla EFICIENCIA_DETALLE con filtro por fecha
    $sql = "SELECT * FROM EFICIENCIA_DETALLES_TOTAL
            WHERE CODIGO_OPERARIO = ? AND FECHA = ?
            ORDER BY INICIO ASC";
    
    $params = [$codigoOperario, $fechaSeleccionada];
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
    
    // Obtener información del operario para el encabezado
    $sqlOperario = "SELECT RESPONSABLE, AREA, TURNO FROM EFICIENCIA_POR_DIA 
                    WHERE CODIGO_OPERARIO = ? AND FECHA = ?";
    $stmtOperario = sqlsrv_query($conn, $sqlOperario, $params);
    
    $operarioInfo = null;
    if ($stmtOperario !== false) {
        $operarioInfo = sqlsrv_fetch_array($stmtOperario, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmtOperario);
    }
    
} else {
    die("No se proporcionó el parámetro 'id'.");
}

sqlsrv_close($conn);

// Función para determinar el color según el porcentaje
function getPerformanceColor($porcentaje) {
    if ($porcentaje <= 70) {
        return 'background: linear-gradient(to right, #ff4d4d, #ff9999); color: black;';
    } elseif ($porcentaje <= 80) {
        return 'background: linear-gradient(to right, #ffff99, #ffcc00); color: black;';
    } else {
        return 'background: linear-gradient(to right, #66ff66, #009933); color: black;';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Operario - <?php echo $fechaSeleccionada; ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .operario-info {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            padding: 12px 8px;
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }
        
        td {
            padding: 10px 8px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        
        tr:hover {
            background-color: #f9fafb;
        }
        
        .percentage-cell {
            font-weight: 600;
            border-radius: 6px;
            padding: 8px;
        }
        
        .summary-row {
            background-color: #f0f9ff;
            font-weight: 600;
            border-top: 2px solid var(--primary-color);
        }
        
        .summary-row td {
            padding: 15px 8px;
            font-size: 14px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
        
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }
        
        .close-btn:hover {
            background-color: #dc2626;
            transform: scale(1.1);
        }
        
        .date-badge {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            th, td {
                padding: 8px 4px;
                font-size: 12px;
            }
            
            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.close()" title="Cerrar ventana">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> Detalles del Operario</h1>
            <div class="date-badge">
                <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($fechaSeleccionada)); ?>
            </div>
        </div>

        <?php if ($operarioInfo): ?>
        <div class="operario-info">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">NOMBRE</div>
                    <div class="info-value"><?php echo htmlspecialchars($operarioInfo['RESPONSABLE']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">CÓDIGO</div>
                    <div class="info-value"><?php echo htmlspecialchars($codigoOperario); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">ÁREA</div>
                    <div class="info-value"><?php echo htmlspecialchars($operarioInfo['AREA']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">TURNO</div>
                    <div class="info-value"><?php echo htmlspecialchars($operarioInfo['TURNO']); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (!empty($data)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-clock"></i> H. INICIO</th>
                            <th><i class="fas fa-clock"></i> H. FINAL</th>
                            <th><i class="fas fa-box"></i> AREA ACT</th>
                            <th><i class="fas fa-box"></i> PRODUCTO</th>
                         
                            <th><i class="fas fa-cubes"></i> UNIDADES FABRI.</th>
                            <th><i class="fas fa-target"></i> ESPERADAS</th>
                            <th><i class="fas fa-stopwatch"></i> TIEMPO TRAB.</th>
                            <th><i class="fas fa-stopwatch"></i> TIEMPO MUERTO.</th>
                            <th><i class="fas fa-percentage"></i> PRODUCTIVIDAD </th>
                                         <th><i class="fas fa-chart-bar"></i> REND. PROD.</th>
                                    <th><i class="fas fa-clock"></i> EFIC. TIEMPO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalUnidades = 0;
                        $totalEsperadas = 0;
                        $totalTiempo = 0;
                        $promedioGeneral = 0;
                        $contador = 0;
                        
                        foreach ($data as $row): 
                            $totalUnidades += (int)$row['UNIDADES'];
                            $totalEsperadas += (int)$row['ESPERADAS'];
                            $totalTiempo += (int)$row['TIEMPO_TRABJADO_MINU'];
                            $promedioGeneral += (int)$row['PORCENTAJE_TOTAL'];
                            $contador++;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['INICIO']); ?></td>
                                <td><?php echo htmlspecialchars($row['FINAL']); ?></td>
                                 <td><?php echo htmlspecialchars($row['AREA_ACTIVIDAD']); ?></td>
                                <td><?php echo htmlspecialchars($row['NOMBRE_PRODUCTO']); ?></td>
                         
                                <td><?php echo number_format((int)$row['UNIDADES']); ?></td>
                                <td><?php echo number_format((int)$row['ESPERADAS']); ?></td>
                                <td><?php echo htmlspecialchars($row['TIEMPO_TRABJADO_MINU']); ?> min</td>
                                <td><?php echo htmlspecialchars($row['TN']); ?> min</td>
                                <td class="percentage-cell" style="<?php echo getPerformanceColor((int)$row['PORCENTAJE_TOTAL']); ?>">
                                    <?php echo (int)$row['PORCENTAJE_TOTAL']; ?>%
                                </td>
                                                <td class="percentage-cell" style="<?php echo getPerformanceColor((int)$row['PORCENTAJE1']); ?>">
                                    <?php echo (int)$row['PORCENTAJE1']; ?>%
                                </td>

                      <td class="percentage-cell" style="<?php echo getPerformanceColor((int)$row['PORCENTAJE2']); ?>">
                                    <?php echo (int)$row['PORCENTAJE2']; ?>%
                                </td>



                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Fila de resumen -->
                        <tr class="summary-row">
                            <td colspan="4"><strong><i class="fas fa-chart-line"></i> TOTALES DEL DÍA</strong></td>
                            <td><strong><?php echo number_format($totalUnidades); ?></strong></td>
                            <td><strong><?php echo number_format($totalEsperadas); ?></strong></td>
                            <td><strong><?php echo $totalTiempo; ?> min</strong></td>
                            <td class="percentage-cell" style="<?php echo getPerformanceColor($contador > 0 ? $promedioGeneral / $contador : 0); ?>">
                                <strong><?php echo $contador > 0 ? round($promedioGeneral / $contador) : 0; ?>%</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No se encontraron registros para este operario en la fecha seleccionada.</p>
                    <p style="margin-top: 10px; font-size: 14px;">
                        <strong>Operario:</strong> <?php echo htmlspecialchars($codigoOperario); ?><br>
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($fechaSeleccionada)); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Cerrar ventana con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.close();
            }
        });
        
        // Auto-cerrar después de 5 minutos de inactividad
        let inactivityTimer = setTimeout(function() {
            if (confirm('¿Desea mantener esta ventana abierta?')) {
                // Reiniciar el timer si el usuario quiere mantenerla abierta
                inactivityTimer = setTimeout(arguments.callee, 300000); // 5 minutos más
            } else {
                window.close();
            }
        }, 300000); // 5 minutos
        
        // Reiniciar timer con cualquier actividad del usuario
        document.addEventListener('click', function() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(function() {
                window.close();
            }, 300000);
        });
    </script>
</body>
</html>
