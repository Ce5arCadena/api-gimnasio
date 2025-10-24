<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecimiento de Contraseña</title>
    <style>
        body {
            background-color: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: #c9d7ec49;
            max-width: 500px;
            margin: 40px auto;
            padding: 24px;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            font-size: 20px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 16px;
        }
        p {
            font-size: 14px;
            color: #4b5563;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #4338ca;
        }
        .note {
            font-size: 12px;
            color: #6b7280;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Solicitud de Restablecimiento de Contraseña</h2>
        <p>
            Hemos recibido tu solicitud para restablecer la contraseña de tu cuenta. Usa el siguiente código para reestablecerla:
        </p>
        
        <div>
            <span class="btn">
                {{ $token }}
            </span>
        </div>

        <p class="note">
            Si no solicitaste este cambio, por favor ignora este correo.
        </p>
    </div>
</body>
</html>
