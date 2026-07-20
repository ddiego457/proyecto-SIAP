<?php
// Validamos que la variable exista para evitar un error sobre el propio error
if (!isset($failDescript) || empty(trim($failDescript))) {
    $failDescript = "Se ha producido un error interno, pero no se ha especificado el detalle.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Sistema - SIAP</title>
    <style>
        /* Ajusta estos colores para que coincidan con tu sistema SIAP */
        :root {
            --siap-primary: #2c3e50;    /* Color principal (ej. menú lateral o header) */
            --siap-secondary: #34495e;  /* Color secundario */
            --siap-bg: #f8f9fa;         /* Fondo del sistema */
            --siap-danger: #e74c3c;     /* Color para resaltar el error */
            --siap-text: #333333;       /* Color del texto normal */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--siap-bg);
            color: var(--siap-text);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .error-card {
            background: #ffffff;
            border-top: 5px solid var(--siap-danger);
            border-radius: 8px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 600px;
            padding: 40px;
            text-align: center;
        }

        .error-icon {
            font-size: 60px;
            color: var(--siap-danger);
            margin-bottom: 20px;
        }

        .error-title {
            color: var(--siap-primary);
            font-size: 24px;
            margin-bottom: 15px;
        }

        .error-message {
            background-color: #fdf2f2;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 15px;
            margin: 25px 0;
            word-wrap: break-word;
            text-align: left;
        }

        .btn-back {
            display: inline-block;
            background-color: var(--siap-primary);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background-color: var(--siap-secondary);
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Interrupción en el Sistema</h1>
        
        <p>El sistema SIAP ha encontrado un problema y no puede completar la solicitud actual. A continuación se detalla la razón:</p>

        <div class="error-message">
            <!-- Aquí se imprime estrictamente tu variable -->
            <?php echo htmlspecialchars($failDescript); ?>
        </div>

        <a href="javascript:history.back()" class="btn-back">Regresar a la página anterior</a>
    </div>

</body>
</html>