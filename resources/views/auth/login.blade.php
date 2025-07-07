<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Esencia Seguros</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --esencia-primary: #374661;
            --esencia-primary-light: #4a5a7a;
            --esencia-primary-dark: #2a3547;
            --esencia-secondary: #dadada;
            --esencia-text-primary: #374661;
            --esencia-text-secondary: #878787;
            --esencia-success: #28a745;
            --esencia-danger: #dc3545;
            --esencia-border-light: #e9ecef;
            --esencia-shadow-card: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--esencia-secondary) 0%, #f5f5f5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--esencia-shadow-card);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .login-header .logo {
            margin-bottom: 1rem;
        }

        .login-header .logo img {
            max-width: 300px;
            height: auto;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
        }

        .login-header .logo img:hover {
            transform: scale(1.05);
        }

        /* Fallback para el logo si el filtro no funciona */
        .login-header .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1rem;
        }

        .login-header h2 {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating .form-control {
            border: 2px solid var(--esencia-border-light);
            border-radius: 12px;
            padding: 1rem 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: var(--esencia-primary);
            box-shadow: 0 0 0 0.2rem rgba(55, 70, 97, 0.25);
        }

        .form-floating label {
            color: var(--esencia-text-secondary);
            font-weight: 500;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--esencia-primary-dark) 0%, var(--esencia-primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(55, 70, 97, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Loading states */
        .btn-login.loading {
            background: linear-gradient(135deg, var(--esencia-primary-dark) 0%, var(--esencia-primary) 100%);
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .btn-login.loading .loading-spinner {
            display: inline-block;
        }

        .loading-spinner {
            display: none;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .ssn-connection-status {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: none;
        }

        .ssn-connection-status.connecting {
            display: block;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .ssn-connection-status.success {
            display: block;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .ssn-connection-status.error {
            display: block;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .input-group-text {
            background: var(--esencia-primary);
            border: 2px solid var(--esencia-primary);
            color: white;
            border-radius: 12px 0 0 12px;
        }

        .email-field,
        .password-field {
            border-radius: 0 12px 12px 0 !important;
        }

        .error-message {
            color: var(--esencia-danger);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message i {
            font-size: 0.8rem;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--esencia-border-light);
        }

        .login-footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .login-footer-left {
            text-align: left;
        }

        .login-footer-center {
            text-align: center;
        }

        .login-footer-right {
            text-align: right;
        }

        .login-footer p {
            color: var(--esencia-text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .login-footer-logo {
            max-height: 25px;
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .login-footer-logo:hover {
            transform: scale(1.05);
            border-radius: 50%;
        }

        .login-footer-link {
            color: var(--esencia-text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-footer-link:hover {
            color: var(--esencia-primary);
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .login-footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .login-footer-left,
            .login-footer-right {
                text-align: center;
            }
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: var(--esencia-primary);
            opacity: 0.05;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-body {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Esencia Seguros">
            </div>
            <h2>Bienvenido</h2>
        </div>

        <div class="login-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-floating">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control email-field @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               placeholder="Correo electrónico"
                               value="{{ old('email') }}" 
                               required 
                               autofocus>
                        <label for="email" class="visually-hidden">Correo electrónico</label>
                    </div>
                    @error('email')
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-floating">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control password-field @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Contraseña"
                               required>
                        <label for="password" class="visually-hidden">Contraseña</label>
                    </div>
                    @error('password')
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </span>
                    <span class="loading-spinner">
                        <i class="fas fa-spinner me-2"></i>
                    </span>
                </button>

                <div class="ssn-connection-status" id="ssnStatus">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        <span id="ssnStatusText">Conectando con la Superintendencia de Seguros de la Nación...</span>
                    </div>
                </div>
            </form>

            <div class="login-footer">
                <div class="login-footer-content">
                    <div class="login-footer-center" style="margin:auto !important">
                        <p>
                            &copy; {{ date('Y') }}  - 
                            Desarrollado por 
                            <a href="https://innovadevelopers.com" target="_blank" class="login-footer-link">
                                <img src="{{ asset('innova-logo.png') }}" alt="Innova Developers" class="login-footer-logo me-1">
                            </a>
                        </p>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('form[action="{{ route("login") }}"]');
            const loginBtn = document.getElementById('loginBtn');
            const ssnStatus = document.getElementById('ssnStatus');
            const ssnStatusText = document.getElementById('ssnStatusText');
            
            loginForm.addEventListener('submit', function(e) {
                // Mostrar estado de carga
                loginBtn.classList.add('loading');
                ssnStatus.className = 'ssn-connection-status connecting';
                ssnStatusText.textContent = 'Conectando con la Superintendencia de Seguros de la Nación...';
                
                // NO deshabilitar los campos para evitar problemas con el envío
                // El formulario se enviará normalmente
            });
        });
    </script>
</body>
</html> 