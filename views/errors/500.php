<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error (500) — GRM Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2.5rem; max-width: 650px; width: 90%; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .error-icon { width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem; }
        .trace-box { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 1rem; font-family: monospace; font-size: 0.825rem; max-height: 250px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>
    <div class="error-card text-center">
        <div class="error-icon mx-auto">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 class="fw-bold mb-2">System Error Occurred</h3>
        <p class="text-muted mb-4"><?= htmlspecialchars($errorMessage ?? 'An unexpected error occurred while processing your request.') ?></p>
        
        <?php if (!empty($trace) && (function_exists('env') && env('APP_DEBUG', false))): ?>
            <div class="text-start mb-4">
                <label class="fw-semibold text-dark small mb-1">Error Details (Debug Mode Active):</label>
                <div class="trace-box"><?= htmlspecialchars($trace) ?></div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center gap-3">
            <a href="javascript:location.reload()" class="btn btn-primary px-4">
                <i class="bi bi-arrow-clockwise me-1"></i> Try Again
            </a>
            <a href="<?= function_exists('url') ? url('login') : '/' ?>" class="btn btn-outline-secondary px-4">
                <i class="bi bi-house me-1"></i> Home
            </a>
        </div>
    </div>
</body>
</html>
