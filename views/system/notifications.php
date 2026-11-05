<?php
// views/system/notifications.php
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>System Notifications & Alert Log</h3>
            <p class="text-muted small mb-0">Central inbox for low stock warnings, program approvals, and background system updates.</p>
        </div>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
