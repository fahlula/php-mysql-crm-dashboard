<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM Professional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: rgb(26, 42, 128);
            --primary-medium: rgb(59, 56, 160);
            --primary-light: rgb(122, 133, 193);
            --primary-lighter: rgb(178, 176, 232);
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(26, 42, 128, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 2rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
            border: none;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 42, 128, 0.4);
        }
        
        .btn-outline-primary {
            color: var(--primary-medium);
            border-color: var(--primary-medium);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-medium);
            border-color: var(--primary-medium);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e1e5e9;
            padding: 12px 16px;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(122, 133, 193, 0.25);
        }
        
        .input-group-text {
            background-color: var(--primary-lighter);
            border-color: var(--primary-lighter);
            color: var(--primary-dark);
            border-radius: 8px 0 0 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="card-header text-center py-4">
                        <h3><i class="fas fa-briefcase me-2"></i>CRM Professional</h3>
                        <p class="mb-0">Faça login para continuar</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($_SESSION['success']); ?>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-0">Não tem uma conta?</p>
                            <a href="/register" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Criar Conta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>