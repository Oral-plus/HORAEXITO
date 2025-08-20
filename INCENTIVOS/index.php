<?php
$serverName = "HERCULES";
$connectionOptions = ["Database" => "calidad", "Uid" => "sa", "PWD" => "Sky2022*!"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { die(print_r(sqlsrv_errors(), true)); }

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$whereFecha = '';
$params = [];
if ($fechaInicio && $fechaFin) {
    $whereFecha = "WHERE FECHA BETWEEN ? AND ?";
    $params = [$fechaInicio, $fechaFin];
}

$sql = "
SELECT RESPONSABLE,
       [NOMBRE_PRODUCTO],
       [AREA],
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
       END AS VALOR_TOTAL
FROM [calidad].[dbo].[EFICIENCIA_DETALLES_TOTAL]
$whereFecha
ORDER BY VALOR_TOTAL DESC
";

$stmt = sqlsrv_query($conn, $sql, $params);

// Calcular estadísticas
$totalRows = 0;
$totalValor = 0;
$totalDiferencia = 0;
$data = [];
$responsables = [];

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
        $totalRows++;
        $totalValor += $row['VALOR_TOTAL'];
        $totalDiferencia += $row['DIFERENCIA'];
        
        // Contar responsables únicos
        if (!in_array($row['RESPONSABLE'], $responsables)) {
            $responsables[] = $row['RESPONSABLE'];
        }
    }
}

$promedioValor = $totalRows > 0 ? $totalValor / $totalRows : 0;
$totalResponsables = count($responsables);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INCENTIVOS POR PRODUCTIVIDAD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            padding: 16px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-rows: auto 1fr;
            gap: 24px;
            height: calc(100vh - 32px);
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
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 1.1rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            height: 100%;
        }
        
        .left-section {
            display: grid;
            grid-template-rows: auto 1fr;
            gap: 20px;
        }
        
        .filter-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: auto auto auto auto;
            gap: 16px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .filter-input {
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: #4ade80;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2);
        }
        
        .filter-btn {
            padding: 12px 24px;
            background: linear-gradient(45deg, #4ade80, #22c55e);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 222, 128, 0.3);
        }
        
        .date-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.8rem;
            opacity: 0.8;
            text-align: center;
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
        }
        
        .table-header h2 {
            font-size: 1.5rem;
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
            font-size: 0.9rem;
        }
        
        .modern-table th {
            background: rgba(255, 255, 255, 0.15);
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .modern-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modern-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .modern-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .valor-cell {
            font-weight: 600;
            color: #4ade80;
        }
        
        .responsable-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .area-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            height: fit-content;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
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
            font-size: 2.2rem;
            font-weight: 700;
            margin: 12px 0;
            background: linear-gradient(45deg, #4ade80, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            opacity: 0.8;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
            font-size: 1.1rem;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #4ade80, #22c55e);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(74, 222, 128, 0.4);
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 15px 40px rgba(74, 222, 128, 0.6);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 12px;
                gap: 16px;
            }
            
            .main-content {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .modern-table {
                font-size: 0.8rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 10px 12px;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <!-- Header -->
        <div class="header">
            <h1>🏭 INCENTIVOS POR PRODUCTIVIDAD</h1>
           
        </div>
        
        <!-- Contenido principal con layout izquierda-derecha -->
        <div class="main-content">
            <!-- Sección izquierda: Filtros y Tabla -->
            <div class="left-section">
                <!-- Filtro de fechas -->
                <div class="filter-container">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label class="filter-label">📅 Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="filter-input" 
                                   value="<?php echo htmlspecialchars($fechaInicio); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">📅 Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="filter-input" 
                                   value="<?php echo htmlspecialchars($fechaFin); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">
                                🔍 Consultar
                            </button>
                        </div>

                        <button  class="filter-btn"onclick="exportarTabla()">📤 Exportar a Excel</button>

                           <div class="filter-group">
    <a href="../NOMINA/index.php">
       
            🔍 Otras Novedades
     
    </a>
</div>


                        <div class="filter-group">
                            <div class="date-info">
                                🕒 Hoy: <?php echo date('d/m/Y'); ?><br>
                                <small style="opacity: 0.7;">Cualquier rango disponible</small>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tabla de datos -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>📊 Detalle de Producción</h2>
                        <small style="opacity: 0.7;">
                            📅 Desde: <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> 
                            hasta: <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                        </small>
                    </div>
                    
                    <div class="table-wrapper">
                        <?php if (empty($data)): ?>
                            <div class="no-results">
                                📭 No se encontraron resultados en el rango seleccionado
                            </div>
                        <?php else: ?>
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-info-circle"></i> Responsable</th>
                                      <th><i class="fas fa-info-circle"></i> Detalles</th>
                                       
                                        <th><i class="fas fa-info-circle"></i> Area</th>
                                       
                                        <th><i class="fas fa-info-circle"></i> Valor Total</th>
                                    </tr>
                                </thead>
                               <tbody>
    <?php
    // Agrupar los valores por responsable
    $responsables = [];
    foreach ($data as $row) {
        $responsable = $row['RESPONSABLE'];
        $area = $row['AREA'];
        $valor = floatval(str_replace(',', '', $row['VALOR_TOTAL']));

        if (!isset($responsables[$responsable])) {
            $responsables[$responsable] = [
                'AREA' => $area,
                'VALOR_TOTAL' => 0
            ];
        }

        $responsables[$responsable]['VALOR_TOTAL'] += $valor;
    }

    // Ordenar de mayor a menor por VALOR_TOTAL
    uasort($responsables, function ($a, $b) {
        return $b['VALOR_TOTAL'] <=> $a['VALOR_TOTAL'];
    });
    ?>

    <?php foreach ($responsables as $responsable => $info): ?>
    <tr>
        <td>
            <span class="responsable-badge">
                <?php echo htmlspecialchars($responsable); ?>
            </span>
        </td>
<td>
                                           <button class="abrir-pestaña"
    data-url="detallesincentivos.php?id=<?php echo urlencode($responsable); ?>&fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>">
    +
</button>

        <td>

        
            <span class="area-badge">
                <?php echo htmlspecialchars($info['AREA']); ?>
            </span>
        </td>

        <td class="valor-cell">
            $<?php echo number_format($info['VALOR_TOTAL'], 0); ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sección derecha: Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Registros</div>
                    <div class="stat-value"><?php echo number_format($totalRows); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Valor Total</div>
                    <div class="stat-value">$<?php echo number_format($totalValor, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Promedio</div>
                    <div class="stat-value">$<?php echo number_format($promedioValor, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Responsables</div>
                    <div class="stat-value" style="color: #667eea;"><?php echo $totalResponsables; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Diferencia Total</div>
                    <div class="stat-value" style="color: #fbbf24;"><?php echo number_format($totalDiferencia); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rango de Fechas</div>
                    <div class="stat-value" style="font-size: 1.2rem; color: #4ade80;">
                        <?php 
                        $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60*60*24) + 1;
                        echo $dias . " día" . ($dias != 1 ? "s" : "");
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <button class="refresh-btn" onclick="location.reload()" title="Actualizar datos">
        🔄
    </button>
    
    <script>
        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
        
        // Optimización táctil para tablet
        document.querySelectorAll('.modern-table tr').forEach(row => {
            row.addEventListener('touchstart', function() {
                this.style.background = 'rgba(255, 255, 255, 0.1)';
            });
            
            row.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.background = '';
                }, 200);
            });
        });
        
        // Auto-submit del formulario al cambiar fechas
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('change', function() {
                // Opcional: auto-submit cuando cambien las fechas
                // this.form.submit();
            });
        });


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
    </script>
    <script>
function exportarTabla() {
    var tabla = document.querySelector(".modern-table");
    var html = tabla.outerHTML.replace(/ /g, '%20');

    var nombreArchivo = 'exportacion.xls';

    var enlace = document.createElement('a');
    enlace.href = 'data:application/vnd.ms-excel,' + html;
    enlace.download = nombreArchivo;
    enlace.click();
}
</script>

</body>
</html>

<?php
sqlsrv_close($conn);
?>