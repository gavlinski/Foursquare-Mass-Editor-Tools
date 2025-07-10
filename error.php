<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Elio Tools</title>
    <link rel="stylesheet" type="text/css" href="estilo.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .error-title {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .error-message {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .retry-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .retry-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">⚠️ Erro de Autenticação</h1>
        <p class="error-message">
            Ocorreu um erro ao tentar autenticar com o Foursquare. 
            Por favor, tente novamente ou verifique suas credenciais.
        </p>
        <a href="index.php" class="retry-button">Tentar Novamente</a>
    </div>
</body>
</html>
