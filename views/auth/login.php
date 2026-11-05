<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GRM Inventory</title>
    <meta name="description" content="Login to GRM Inventory Management System">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-gradient);
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            padding: 2.5rem;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo i {
            font-size: 3rem;
            color: var(--primary);
            display: block;
            margin-bottom: 0.5rem;
        }
        .login-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .login-logo p {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .form-floating label { color: #64748b; }
        .form-floating .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem 0.75rem;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .form-floating .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-login {
            background: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
            color: #fff;
        }
        .alert { border-radius: 10px; font-size: 0.875rem; }
        .password-toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 1.25rem;
            z-index: 10;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        .password-toggle-btn:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-box-seam-fill"></i>
                <h1>GRM Inventory</h1>
                <p>Inventory Management System</p>
            </div>

            <!-- Flash Messages -->
            <?php foreach (['success', 'error', 'warning'] as $type): ?>
                <?php $messages = Session::getFlash($type); ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> mb-3">
                        <?= e($message) ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <form action="<?= url('login') ?>" method="POST" id="loginForm">
                <?= CSRF::tokenField() ?>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username or Email" value="<?= e(old('username')) ?>" required autofocus>
                    <label for="username"><i class="bi bi-person me-1"></i>Username or Email</label>
                </div>
                
                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control pe-5" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
                    <button type="button" class="password-toggle-btn" id="togglePassword" tabindex="-1" title="Show/Hide Password" aria-label="Toggle password visibility">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword && passwordInput && eyeIcon) {
            togglePassword.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.classList.toggle('bi-eye', !isPassword);
                eyeIcon.classList.toggle('bi-eye-slash', isPassword);
            });
        }
    </script>
</body>
</html>
