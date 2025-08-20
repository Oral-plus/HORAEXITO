<?php
$serverName = "HERCULES";
$connectionOptions = ["Database" => "calidad", "Uid" => "sa", "PWD" => "Sky2022*!"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$responsable = $_GET['id'] ?? '';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

$sql = "
SELECT RESPONSABLE,
       [NOMBRE_PRODUCTO],
       [AREA],
       [UNIDADES],
       [ESPERADAS],
       [TIEMPO_TRABJADO_SEGUN],
       [FECHA],
       CASE 
           WHEN ([UNIDADES] - [ESPERADAS]) < 0 THEN 0
           ELSE ([UNIDADES] - [ESPERADAS])
       END AS DIFERENCIA,
       CASE 
           WHEN [TIEMPO_TRABJADO_SEGUN] = 0 THEN 0
           ELSE 19231.0 / (26400 * (([ESPERADAS] * 1.2) / [TIEMPO_TRABJADO_SEGUN]))
       END AS TARIFA,
       CASE 
           WHEN ([UNIDADES] - [ESPERADAS]) < 0 THEN 0
           ELSE ([UNIDADES] - [ESPERADAS])
       END * 
       CASE 
           WHEN [TIEMPO_TRABJADO_SEGUN] = 0 THEN 0
           ELSE 19231.0 / (26400 * (([ESPERADAS] * 1.2) / [TIEMPO_TRABJADO_SEGUN]))
       END AS VALOR_TOTAL,
       CASE 
           WHEN [ESPERADAS] = 0 THEN 0
           ELSE ([UNIDADES] * 100.0 / [ESPERADAS])
       END AS PORCENTAJE_EFICIENCIA
FROM [calidad].[dbo].[EFICIENCIA_DETALLES_TOTAL]
WHERE FECHA BETWEEN ? AND ? AND RESPONSABLE = ?
ORDER BY FECHA DESC, VALOR_TOTAL DESC
";

$params = [$fechaInicio, $fechaFin, $responsable];
$stmt = sqlsrv_query($conn, $sql, $params);

// Calcular totales y estadísticas
$data = [];
$totalValor = 0;
$totalUnidades = 0;
$totalEsperadas = 0;
$totalDiferencia = 0;

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
        $totalValor += $row['VALOR_TOTAL'];
        $totalUnidades += $row['UNIDADES'];
        $totalEsperadas += $row['ESPERADAS'];
        $totalDiferencia += $row['DIFERENCIA'];
    }
}

$promedioEficiencia = $totalEsperadas > 0 ? ($totalUnidades * 100 / $totalEsperadas) : 0;

// Función para determinar color según eficiencia
function getEfficiencyColor($porcentaje) {
    if ($porcentaje >= 100) {
        return '#10b981'; // Verde
    } elseif ($porcentaje >= 80) {
        return '#f59e0b'; // Amarillo
    } else {
        return '#ef4444'; // Rojo
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Producción - <?php echo htmlspecialchars($responsable); ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-rows: auto auto 1fr;
            gap: 24px;
            min-height: calc(100vh - 40px);
        }
        
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 1.1rem;
            margin-top: 8px;
        }
        
        .date-badge {
            display: inline-block;
            background: linear-gradient(45deg, #4ade80, #22c55e);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 8px 0;
            color: #4ade80;
        }
        
        .stat-label {
            opacity: 0.8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .efficiency-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .table-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .table-header {
            padding: 20px 24px;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            font-size: 1.5rem;
            font-weight: 500;
        }
        
        .record-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .table-wrapper {
            flex: 1;
            overflow: auto;
            max-height: 600px;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        .modern-table th {
            background: rgba(255, 255, 255, 0.15);
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 0.8rem;
        }
        
        .modern-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
        }
        
        .modern-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .modern-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .product-badge {
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .area-badge {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .value-cell {
            font-weight: 600;
            color: #4ade80;
        }
        
        .efficiency-cell {
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 12px;
            text-align: center;
        }
        
        .date-cell {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
            font-size: 1.1rem;
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(45deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .close-btn:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        .summary-row {
            background: rgba(74, 222, 128, 0.1) !important;
            border-top: 2px solid #4ade80;
            font-weight: 600;
        }
        
        .summary-row td {
            padding: 16px 12px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 12px;
                gap: 16px;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .modern-table {
                font-size: 0.7rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 8px 6px;
            }
            
            .product-badge {
                max-width: 80px;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); }
            100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }
    </style>
</head>
<body>
    <button class="close-btn" onclick="window.close()" title="Cerrar ventana">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="container fade-in">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-user-cog"></i>
                Detalles de Producción
            </h1>
            <p><strong><?php echo htmlspecialchars($responsable); ?></strong></p>
            <div class="date-badge">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('d/m/Y', strtotime($fechaInicio)) . " - " . date('d/m/Y', strtotime($fechaFin)); ?>
            </div>
        </div>
        
        <!-- Estadísticas resumen -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-label">Total Registros</div>
                <div class="stat-value"><?php echo count($data); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Valor Total</div>
                <div class="stat-value">$<?php echo number_format($totalValor, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Unidades</div>
                <div class="stat-value" style="color: #818cf8;"><?php echo number_format($totalUnidades); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Unidades Esperadas</div>
                <div class="stat-value" style="color: #f59e0b;"><?php echo number_format($totalEsperadas); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Diferencia Total</div>
                <div class="stat-value" style="color: #10b981;"><?php echo number_format($totalDiferencia); ?></div>
            </div>
            <div class="stat-card <?php echo $promedioEficiencia >= 100 ? 'pulse' : ''; ?>">
                <div class="efficiency-indicator" style="background-color: <?php echo getEfficiencyColor($promedioEficiencia); ?>;">
                    <?php echo number_format($promedioEficiencia, 1); ?>%
                </div>
                <div class="stat-label">Eficiencia Promedio</div>
            </div>
        </div>
        
        <!-- Tabla de datos -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-table"></i> Detalle por Producto</h2>
                <div class="record-count">
                    <?php echo count($data); ?> registros
                </div>
            </div>
            
            <div class="table-wrapper">
                <?php if (empty($data)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No se encontraron resultados para este operario</p>
                        <p style="font-size: 0.9rem; margin-top: 10px; opacity: 0.6;">
                            Rango: <?php echo date('d/m/Y', strtotime($fechaInicio)) . " - " . date('d/m/Y', strtotime($fechaFin)); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Fecha</th>
                                <th><i class="fas fa-box"></i> Producto</th>
                                <th><i class="fas fa-building"></i> Área</th>
                                <th><i class="fas fa-chart-line"></i> Unidades</th>
                                <th><i class="fas fa-target"></i> Esperadas</th>
                                <th><i class="fas fa-plus-circle"></i> Diferencia</th>
                                <th><i class="fas fa-percentage"></i> Eficiencia</th>
                                <th><i class="fas fa-dollar-sign"></i> Tarifa</th>
                                <th><i class="fas fa-money-bill-wave"></i> Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td class="date-cell">
                                        <?php 
                                        if ($row['FECHA'] instanceof DateTime) {
                                            echo $row['FECHA']->format('d/m/Y');
                                        } else {
                                            echo date('d/m/Y', strtotime($row['FECHA']));
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="product-badge" title="<?php echo htmlspecialchars($row['NOMBRE_PRODUCTO']); ?>">
                                            <?php echo htmlspecialchars($row['NOMBRE_PRODUCTO']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="area-badge">
                                            <?php echo htmlspecialchars($row['AREA']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 600;">
                                        <?php echo number_format($row['UNIDADES']); ?>
                                    </td>
                                    <td style="text-align: center; color: #f59e0b;">
                                        <?php echo number_format($row['ESPERADAS']); ?>
                                    </td>
                                    <td style="text-align: center; color: #10b981; font-weight: 600;">
                                        <?php echo number_format($row['DIFERENCIA']); ?>
                                    </td>
                                    <td>
                                        <div class="efficiency-cell" style="background-color: <?php echo getEfficiencyColor($row['PORCENTAJE_EFICIENCIA']); ?>;">
                                            <?php echo number_format($row['PORCENTAJE_EFICIENCIA'], 1); ?>%
                                        </div>
                                    </td>
                                    <td class="value-cell">
                                        $<?php echo number_format($row['TARIFA'], 2); ?>
                                    </td>
                                    <td class="value-cell" style="font-size: 0.9rem; font-weight: 700;">
                                        $<?php echo number_format($row['VALOR_TOTAL'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Fila de totales -->
                            <tr class="summary-row">
                                <td colspan="3" style="text-align: center;"><strong>TOTALES</strong></td>
                                <td style="text-align: center;"><strong><?php echo number_format($totalUnidades); ?></strong></td>
                                <td style="text-align: center;"><strong><?php echo number_format($totalEsperadas); ?></strong></td>
                                <td style="text-align: center;"><strong><?php echo number_format($totalDiferencia); ?></strong></td>
                                <td style="text-align: center;"><strong><?php echo number_format($promedioEficiencia, 1); ?>%</strong></td>
                                <td style="text-align: center;"><strong>-</strong></td>
                                <td style="text-align: center;"><strong>$<?php echo number_format($totalValor, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
        
        // Cerrar ventana con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.close();
            }
        });
        
        // Auto-cerrar después de 10 minutos de inactividad
        let inactivityTimer = setTimeout(function() {
            if (confirm('¿Desea mantener esta ventana abierta?')) {
                inactivityTimer = setTimeout(arguments.callee, 600000); // 10 minutos más
            } else {
                window.close();
            }
        }, 600000); // 10 minutos
        
        // Reiniciar timer con cualquier actividad del usuario
        document.addEventListener('click', function() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(function() {
                if (confirm('¿Desea mantener esta ventana abierta?')) {
                    inactivityTimer = setTimeout(arguments.callee, 600000);
                } else {
                    window.close();
                }
            }, 600000);
        });
        
        // Efecto hover mejorado para filas de tabla
        document.querySelectorAll('.modern-table tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>

<?php
sqlsrv_close($conn);
?>