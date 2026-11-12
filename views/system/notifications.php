<?php
// views/system/notifications.php
$unreadCount = 0;
foreach ($notifications ?? [] as $notifItem) {
    if (empty($notifItem['is_read'])) {
        $unreadCount++;
    }
}
?>
<div class="container-fluid py-4">
    <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= e(Session::getFlash('success')[0] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (Session::hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= e(Session::getFlash('error')[0] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>System Notifications & Alert Log</h3>
            <p class="text-muted small mb-0">Central inbox for low stock warnings, program approvals, and background system updates.</p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="<?= url('system/notifications/read-all') ?>">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-check2-all me-1"></i>Mark All Read (<?= $unreadCount ?>)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Status</th>
                        <th>Type</th>
                        <th>Message Title</th>
                        <th>Details</th>
                        <th>Created Time</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No system notifications recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <tr class="<?= empty($n['is_read']) ? 'bg-light' : '' ?>">
                                <td class="ps-3">
                                    <?php if (empty($n['is_read'])): ?>
                                        <span class="badge bg-danger">Unread</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info text-dark"><?= e($n['type'] ?? 'SYSTEM') ?></span></td>
                                <td class="fw-bold text-dark"><?= e($n['title'] ?? $n['message'] ?? '-') ?></td>
                                <td><small class="text-muted"><?= e($n['message'] ?? '-') ?></small></td>
                                <td><small class="text-muted"><?= formatDateTime($n['created_at']) ?></small></td>
                                <td class="text-end pe-3">
                                    <?php if (empty($n['is_read'])): ?>
                                        <form method="POST" action="<?= url('system/notifications/read/' . (int)$n['id']) ?>" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-xs btn-outline-secondary">Mark Read</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
