<?php
$user = class_exists('Session') ? Session::user() : null;
$userRole = $user['role_name'] ?? ($user['role_slug'] ?? 'Staff');
$userName = $user['full_name'] ?? ($user['username'] ?? 'User');

// Determine best dashboard link based on permissions
$dashboardUrl = url('login');
if ($user) {
    if (function_exists('hasPermission')) {
        if (hasPermission('dashboard', 'view')) {
            $dashboardUrl = url('admin/dashboard');
        } elseif (hasPermission('production', 'view') || hasPermission('sellers', 'view') || hasPermission('cutting', 'view') || hasPermission('programs', 'view')) {
            $dashboardUrl = url('production/dashboard');
        } elseif (hasPermission('warehouse', 'view') || hasPermission('inward', 'view') || hasPermission('stock', 'view')) {
            $dashboardUrl = url('warehouse/dashboard');
        } elseif (hasPermission('shop', 'view') || hasPermission('pos', 'view')) {
            $dashboardUrl = url('shop/dashboard');
        } elseif (hasPermission('assets', 'view')) {
            $dashboardUrl = url('asset/dashboard');
        } else {
            $dashboardUrl = url('production/dashboard');
        }
    } else {
        $dashboardUrl = url('admin/dashboard');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted — GRM Inventory</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --card-bg: rgba(255, 255, 255, 0.96);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 50% 20%, #f1f5f9 0%, #e2e8f0 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .access-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(0, 0, 0, 0.04);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInScale 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .access-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #f59e0b, #ef4444, #6366f1);
        }
        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.96) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .icon-container {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-pulse {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #fef3c7;
            animation: pulse 2.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.15); opacity: 0.4; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .icon-wrapper {
            position: relative;
            z-index: 2;
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: var(--warning-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            box-shadow: 0 10px 20px -5px rgba(245, 158, 11, 0.4);
        }
        .app-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 100px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 1.25rem;
        }
        .app-badge i {
            color: #6366f1;
        }
        .title {
            font-size: 1.625rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        .message-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            margin: 1.25rem 0 1.5rem;
            text-align: left;
        }
        .message-box .message-text {
            color: #92400e;
            font-size: 0.9375rem;
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .message-box i {
            font-size: 1.15rem;
            margin-top: 1px;
            flex-shrink: 0;
        }
        .user-info-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.8125rem;
            color: #334155;
            margin-bottom: 1.75rem;
        }
        .action-btn-primary {
            background: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .action-btn-primary:hover {
            background: #1e293b;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.2);
        }
        .action-btn-secondary {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .action-btn-secondary:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .help-text {
            margin-top: 1.75rem;
            font-size: 0.8125rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="access-card">
        <!-- Brand Badge -->
        <div class="app-badge">
            <i class="bi bi-box-seam-fill"></i>
            <span>GRM Inventory Management</span>
        </div>

        <!-- Lock Icon with Animated Glow -->
        <div class="icon-container">
            <div class="icon-pulse"></div>
            <div class="icon-wrapper">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
        </div>

        <!-- Title -->
        <h2 class="title">Access Restricted</h2>

        <!-- Context Explanation -->
        <div class="message-box">
            <p class="message-text">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($errorMessage ?? 'Your current staff role does not have permission to access or perform actions in this section.') ?></span>
            </p>
        </div>

        <!-- User Role Pill -->
        <?php if ($user): ?>
            <div class="user-info-pill">
                <i class="bi bi-person-badge text-primary"></i>
                <span>Signed in as <strong><?= htmlspecialchars($userName) ?></strong></span>
                <span class="text-muted">•</span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><?= htmlspecialchars($userRole) ?></span>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="javascript:history.length > 1 ? history.back() : window.location.href='<?= $dashboardUrl ?>';" class="action-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                <span>Go Back</span>
            </a>
            <a href="<?= $dashboardUrl ?>" class="action-btn-primary">
                <i class="bi bi-speedometer2"></i>
                <span>My Workspace Dashboard</span>
            </a>
        </div>

        <!-- Admin Assistance Note -->
        <p class="help-text">
            Need access to this feature? Please contact your <strong>System Administrator</strong> to update your role permissions in the Admin Panel.
        </p>
    </div>
</body>
</html>
