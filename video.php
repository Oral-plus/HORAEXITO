<!DOCTYPE html>
<html>
<head>
    <title>Videos programados - Múltiples horarios</title>
    <style>
        .video-container {
            display: none;
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        
        .video-container.active {
            border-color: #0066cc;
            background-color: #e6f3ff;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            text-align: center;
        }
        
        .info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        
        .video-info {
            margin: 15px 0;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            border-left: 4px solid #0066cc;
            text-align: left;
        }
        
        .horarios-list {
            margin: 10px 0;
        }
        
        .horario-item {
            display: inline-block;
            margin: 5px;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .horario-item.completado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .horario-item.activo {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
        }
        
        .status {
            font-weight: bold;
            margin: 10px 0;
        }
        
        video {
            max-width: 100%;
            height: auto;
        }
        
        .contador-reproducciones {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="info">
        <h1>Videos Programados - Múltiples Horarios</h1>
        <p id="horaActual">Hora actual: --:--</p>
        
        <div class="video-info">
            <strong>📹 Video 1: Universo de los niños</strong>
            <div class="horarios-list">
                <strong>Horarios programados:</strong>
                <div id="horarios-video1"></div>
            </div>
            <div id="status1" class="status">Esperando próximo horario...</div>
            <div id="contador1" class="contador-reproducciones"></div>
        </div>
        
        <div class="video-info">
            <strong>📹 Video 2: Segundo video</strong>
            <div class="horarios-list">
                <strong>Horarios programados:</strong>
                <div id="horarios-video2"></div>
            </div>
            <div id="status2" class="status">Esperando próximo horario...</div>
            <div id="contador2" class="contador-reproducciones"></div>
        </div>
    </div>

    <!-- Primer video -->
    <div id="container1" class="video-container">
        <h3>🎬 Video 1 - Universo de los niños</h3>
        <p id="horario-actual1" class="status"></p>
        <video id="video1" width="640" height="360" controls autoplay>
            <source src="https://oral-plus.com/videos/Universo%20de%20los%20ninos%20Oral%20Plus.mp4" type="video/mp4">
            Tu navegador no soporta el video.
        </video>
    </div>

    <!-- Segundo video -->
    <div id="container2" class="video-container">
        <h3>🎬 Video 2 - Segundo video</h3>
        <p id="horario-actual2" class="status"></p>
        <video id="video2" width="640" height="360" controls autoplay>
            <source src="https://oral-plus.com/videos/Oral%20Plus_seda%20dental_v2.mp4" type="video/mp4">
            Tu navegador no soporta el video.
        </video>
    </div>

    <script>
        // Configuración de los videos con múltiples horarios
        const videosConfig = [
            {
                id: 'video1',
                containerId: 'container1',
                statusId: 'status1',
                contadorId: 'contador1',
                horarioActualId: 'horario-actual1',
                horariosListId: 'horarios-video1',
                nombre: 'Universo de los niños',
                horariosProgramados: ['10:54', '11:00', '14:30', '16:15'],
                horariosReproducidos: []
            },
            {
                id: 'video2',
                containerId: 'container2',
                statusId: 'status2',
                contadorId: 'contador2',
                horarioActualId: 'horario-actual2',
                horariosListId: 'horarios-video2',
                nombre: 'Segundo video',
                horariosProgramados: ['10:55', '12:00', '15:00', '17:30'],
                horariosReproducidos: []
            }
        ];

        const horaActualElement = document.getElementById("horaActual");
        let verificacionInterval;

        // Inicializar videos y mostrar horarios
        function inicializarVideos() {
            videosConfig.forEach(config => {
                const video = document.getElementById(config.id);
                const container = document.getElementById(config.containerId);
                const horariosListElement = document.getElementById(config.horariosListId);
                
                // Asegurar que el video esté pausado y oculto
                video.pause();
                container.style.display = "none";
                
                // Mostrar horarios programados
                mostrarHorariosProgramados(config);
                actualizarContador(config);
            });
        }

        function mostrarHorariosProgramados(config) {
            const horariosListElement = document.getElementById(config.horariosListId);
            horariosListElement.innerHTML = '';
            
            config.horariosProgramados.forEach(horario => {
                const horarioElement = document.createElement('span');
                horarioElement.className = 'horario-item';
                horarioElement.textContent = horario;
                horarioElement.id = `horario-${config.id}-${horario.replace(':', '')}`;
                
                if (config.horariosReproducidos.includes(horario)) {
                    horarioElement.classList.add('completado');
                    horarioElement.textContent += ' ✅';
                }
                
                horariosListElement.appendChild(horarioElement);
            });
        }

        function actualizarContador(config) {
            const contadorElement = document.getElementById(config.contadorId);
            const total = config.horariosProgramados.length;
            const reproducidos = config.horariosReproducidos.length;
            const pendientes = total - reproducidos;
            
            contadorElement.textContent = `Reproducidos: ${reproducidos}/${total} | Pendientes: ${pendientes}`;
        }

        function actualizarHoraActual() {
            const ahora = new Date();
            const hora = ahora.getHours().toString().padStart(2, '0');
            const minutos = ahora.getMinutes().toString().padStart(2, '0');
            const horaActual = `${hora}:${minutos}`;
            
            horaActualElement.textContent = `Hora actual: ${horaActual}`;
            return horaActual;
        }

        function verificarYReproducirVideos() {
            const horaActual = actualizarHoraActual();
            let hayPendientes = false;

            videosConfig.forEach(config => {
                // Verificar cada horario programado del video
                config.horariosProgramados.forEach(horarioProgramado => {
                    // Solo reproducir si es la hora programada Y este horario específico no se ha reproducido
                    if (horaActual === horarioProgramado && !config.horariosReproducidos.includes(horarioProgramado)) {
                        reproducirVideo(config, horarioProgramado);
                    }
                });
                
                // Verificar si aún hay horarios pendientes para este video
                if (config.horariosReproducidos.length < config.horariosProgramados.length) {
                    hayPendientes = true;
                }
            });

            // Si no hay más reproducciones pendientes, detener la verificación
            if (!hayPendientes) {
                clearInterval(verificacionInterval);
                console.log("Todos los horarios de todos los videos han sido reproducidos. Deteniendo verificación.");
            }
        }

        function reproducirVideo(config, horarioProgramado) {
            const video = document.getElementById(config.id);
            const container = document.getElementById(config.containerId);
            const statusElement = document.getElementById(config.statusId);
            const horarioActualElement = document.getElementById(config.horarioActualId);
            
            // Mostrar el contenedor del video
            container.style.display = "block";
            container.classList.add("active");
            
            // Mostrar información del horario actual
            horarioActualElement.textContent = `🕐 Reproduciendo horario: ${horarioProgramado}`;
            
            // Configurar el video
            video.muted = false;
            video.currentTime = 0; // Reiniciar el video desde el inicio
            
            video.play().then(() => {
                // Marcar este horario como reproducido
                config.horariosReproducidos.push(horarioProgramado);
                
                // Actualizar interfaz
                statusElement.textContent = `✅ Reproducido a las ${horarioProgramado}`;
                statusElement.style.color = "green";
                
                // Actualizar la visualización de horarios
                mostrarHorariosProgramados(config);
                actualizarContador(config);
                
                // Marcar el horario como activo visualmente
                const horarioElement = document.getElementById(`horario-${config.id}-${horarioProgramado.replace(':', '')}`);
                if (horarioElement) {
                    horarioElement.classList.add('activo');
                }
                
                console.log(`Video ${config.nombre} reproducido correctamente a las ${horarioProgramado}`);
                
                // Ocultar el video después de que termine (opcional)
                video.addEventListener('ended', () => {
                    setTimeout(() => {
                        container.style.display = "none";
                        container.classList.remove("active");
                        
                        // Actualizar estado para el próximo horario
                        const proximoHorario = obtenerProximoHorario(config);
                        if (proximoHorario) {
                            statusElement.textContent = `⏰ Próximo horario: ${proximoHorario}`;
                            statusElement.style.color = "#0066cc";
                        } else {
                            statusElement.textContent = "🏁 Todos los horarios completados";
                            statusElement.style.color = "#666";
                        }
                    }, 2000);
                }, { once: true });
                
            }).catch(error => {
                console.log(`Error al reproducir el video ${config.nombre} a las ${horarioProgramado}:`, error);
                statusElement.textContent = `❌ Error a las ${horarioProgramado} - Haz clic para reproducir`;
                statusElement.style.color = "red";
                
                // Aunque haya error, marcamos este horario como "reproducido" para no intentarlo de nuevo
                config.horariosReproducidos.push(horarioProgramado);
                mostrarHorariosProgramados(config);
                actualizarContador(config);
            });
        }

        function obtenerProximoHorario(config) {
            const horariosRestantes = config.horariosProgramados.filter(
                horario => !config.horariosReproducidos.includes(horario)
            );
            return horariosRestantes.length > 0 ? horariosRestantes[0] : null;
        }

        // Actualizar la hora cada segundo
        setInterval(actualizarHoraActual, 1000);
        
        // Comprobar cada 5 segundos si es hora de mostrar algún video
        verificacionInterval = setInterval(verificarYReproducirVideos, 5000);

        // Inicializar
        inicializarVideos();
        actualizarHoraActual();
    </script>
</body>
</html>