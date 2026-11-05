<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found (404) — GRM Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; max-width: 500px; width: 90%; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .error-icon { width: 64px; height: 64px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="error-card text-center">
        <div class="error-icon mx-auto">
            <i class="bi bi-compass"></i>
        </div>
        <h3 class="fw-bold mb-2">404 - Page Not Found</h3>
        <p class="text-muted mb-4"><?= htmlspecialchars($errorMessage ?? 'The page or resource you requested could not be found.') ?></p>
        <a href="<?= function_exists('url') ? url('admin/dashboard') : '/' ?>" class="btn btn-primary px-4">
            <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
        </a>
    </div>
</body>
</html>
