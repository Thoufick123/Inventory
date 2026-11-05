<?php
// views/system/backup.php — Enterprise Backup Center & Test Data Management
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-database-gear text-primary me-2"></i>Data Backup & Disaster Recovery</h3>
            <p class="text-muted small mb-0">Loss-free full database backups, instant SQL/ZIP exports, snapshot restore points, and testing environment tools.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailBackupModal" title="Send latest backup to abiprasath6@gmail.com">
                <i class="bi bi-envelope-arrow-up me-1"></i> Send Backup to Email
            </button>
            <a href="<?= url('system/backup/export-zip') ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" title="Export complete whole database backup (all tables & data) and auto-send to abiprasath6@gmail.com">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Export Data Backup
            </a>
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importBackupModal" title="Upload and import backup file">
                <i class="bi bi-upload me-1"></i> Import Backup File
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createBackupModal" title="Create and email a restore snapshot to abiprasath6@gmail.com">
                <i class="bi bi-plus-lg me-1"></i> Create Snapshot
            </button>
        </div>
    </div>

    <!-- Safety First & Testing Zone Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-4 border-info">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-circle bg-info-subtle p-3 text-info me-3 flex-shrink-0">
                        <i class="bi bi-shield-check fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Zero Data Loss & Auto-Rollback Guarantee</h6>
                        <p class="mb-0 text-muted small">Every backup export contains 100% complete table structures, constraints, and data with UTF-8 character integrity. Before any restore or test data purge, the system automatically takes a pre-action safety snapshot.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-4 border-danger">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center me-3">
                        <div class="rounded-circle bg-danger-subtle p-3 text-danger me-3 flex-shrink-0">
                            <i class="bi bi-radioactive fs-3"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">Testing Mode: Reset Test Data</h6>
                            <p class="mb-0 text-muted small">Purge test production, rolls, purchases, POS sales & stock balances cleanly.</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#wipeTestDataModal">
                        <i class="bi bi-trash3-fill me-1"></i> Delete All Test Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup List Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-archive me-2 text-primary"></i>Available Database Backup Snapshots</h5>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><?= count($backups) ?> Saved Archives</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Backup Name / Label</th>
                        <th>Type</th>
                        <th>Created Date</th>
                        <th>File Size</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No backups created yet. Click <strong>Create Snapshot</strong> or use <strong>Instant SQL Export</strong> to back up your database.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($backups as $bk): ?>
                            <?php
                            $isPreRestore = ($bk['type'] === 'ROLLBACK_SNAPSHOT');
                            $isPreWipe = ($bk['type'] === 'PRE_WIPE_SAFETY_POINT');
                            
                            $label = $bk['notes'] ?? 'System Backup';
                            if ($label === 'System Backup' || empty($label)) {
                                if ($isPreRestore) {
                                    $label = 'Pre-Restore Safety Point';
                                } elseif ($isPreWipe) {
                                    $label = 'Pre-Wipe Safety Snapshot';
                                } else {
                                    $label = 'Full Database Backup';
                                }
                            }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle <?= $isPreWipe ? 'bg-danger-subtle text-danger' : ($isPreRestore ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary') ?> p-2 me-3 d-flex align-items-center justify-content-center" style="width:38px; height:38px;">
                                            <i class="bi <?= $bk['is_compressed'] ? 'bi-file-earmark-zip' : 'bi-filetype-sql' ?> fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($label) ?></div>
                                            <small class="text-muted font-monospace" style="font-size:11px;"><?= e($bk['filename']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isPreWipe): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="bi bi-shield-shaded me-1"></i>Pre-Wipe Safety</span>
                                    <?php elseif ($isPreRestore): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Safety Point</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="bi bi-database-check me-1"></i>Full Backup</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="fw-semibold text-dark"><?= formatDateTime($bk['created_at']) ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= e($bk['size_formatted']) ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('system/backup/send-email/' . urlencode($bk['filename']) . '?email=abiprasath6@gmail.com') ?>" class="btn btn-light border rounded-pill px-3 me-1 text-info" title="Email this snapshot to abiprasath6@gmail.com" onclick="return confirm('Send backup archive <?= e($bk['filename']) ?> directly to abiprasath6@gmail.com?');">
                                            <i class="bi bi-envelope me-1"></i> Email
                                        </a>
                                        <a href="<?= url('system/backup/download/' . urlencode($bk['filename'])) ?>" class="btn btn-light border rounded-pill px-3 me-1" title="Download File">
                                            <i class="bi bi-download me-1 text-primary"></i> Download
                                        </a>
                                        <button type="button" class="btn btn-light border rounded-pill px-3 me-1 text-warning-emphasis btn-trigger-restore" 
                                                data-filename="<?= e($bk['filename']) ?>" title="Restore Database">
                                            <i class="bi bi-arrow-repeat me-1 text-warning"></i> Restore
                                        </button>
                                        <form method="POST" action="<?= url('system/backup/delete/' . urlencode($bk['filename'])) ?>" class="d-inline" onsubmit="return confirm('Delete backup snapshot <?= e($bk['filename']) ?>?');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-light border rounded-circle" title="Delete Snapshot">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('system/backup/create') ?>">
                <?= CSRF::field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle text-primary me-2"></i>Create &amp; Email Database Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Backup Title / Note</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Manual backup before testing" value="Manual backup">
                    </div>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-envelope-check me-1"></i>The snapshot will also be emailed to <strong>abiprasath6@gmail.com</strong> after it is created.
                    </div>
                    <div class="form-check form-switch bg-light p-3 rounded-3 border ms-0">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="compress" id="compressSwitch" value="1" checked>
                        <label class="form-check-label fw-semibold" for="compressSwitch">Compress as ZIP archive (Recommended)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"><i class="bi bi-envelope-arrow-up me-1"></i> Create &amp; Email Snapshot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Backup Modal -->
<div class="modal fade" id="importBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('system/backup/import') ?>" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-upload text-success me-2"></i>Import Backup File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choose Backup Archive (.zip or .sql) <span class="text-danger">*</span></label>
                        <input type="file" name="backup_file" class="form-control" accept=".zip,.sql" required>
                        <div class="form-text text-muted">Supports complete ZIP backup archives (containing SQL data dumps) or direct .sql files.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note / Description (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Exported backup snapshot">
                    </div>
                    <div class="form-check form-switch bg-light p-3 rounded-3 border mb-2">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="restore_now" id="restoreNowSwitch" value="1" checked>
                        <label class="form-check-label fw-bold text-dark" for="restoreNowSwitch">
                            Restore Database Immediately After Upload
                        </label>
                        <div class="small text-muted ms-4">The system will automatically create a pre-restore safety rollback point and apply the uploaded backup data immediately.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 fw-semibold shadow-sm"><i class="bi bi-cloud-upload me-1"></i> Import & Apply Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('system/backup/restore') ?>">
                <?= CSRF::field() ?>
                <input type="hidden" name="filename" id="restoreFilename">
                <div class="modal-header bg-warning text-dark border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2 text-dark"></i>Confirm Database Restore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> Restoring snapshot <strong id="restoreFilenameDisplay" class="text-dark"></strong> will replace current data. An automatic pre-restore safety snapshot will be created before restoring.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Password (Optional if authenticated)</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter admin password (optional)">
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">Confirmation Phrase</label>
                        <input type="text" name="confirmation" class="form-control font-monospace" value="grm-restore" placeholder="grm-restore">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-semibold"><i class="bi bi-arrow-repeat me-1"></i> Restore Snapshot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Wipe Test Data Modal -->
<div class="modal fade" id="wipeTestDataModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="<?= url('system/backup/wipe-test-data') ?>">
                <?= CSRF::field() ?>
                
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-radioactive me-2"></i>Testing Environment — Delete All Test Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-danger border-0 d-flex align-items-start mb-3">
                        <i class="bi bi-exclamation-octagon-fill fs-4 me-3 mt-1 flex-shrink-0"></i>
                        <div>
                            <strong class="d-block mb-1">Testing Purpose Data Reset</strong>
                            This action will completely purge all testing transactions and reset inventory to a clean fresh state.
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle h-100">
                                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-trash3 me-1"></i> What Will Be Deleted:</h6>
                                <ul class="small text-danger mb-0 ps-3">
                                    <li>Production runs, cutting, stitching & QC batches</li>
                                    <li>Fabric rolls, inward receipts & purchase orders</li>
                                    <li>Warehouse allocations, transfers & deliveries</li>
                                    <li>Stock balances & inventory movement logs</li>
                                    <li>POS sales, payments, receipts & cash registers</li>
                                    <li>All module expenses, damages & asset logs</li>
                                    <li>Test customers & test supplier records</li>
                                    <li><strong>All test staff & non-admin user accounts</strong></li>
                                    <li><strong>All test units, warehouses, shops & sales channels</strong></li>
                                    <li>Audit logs & system notifications</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle h-100">
                                <h6 class="fw-bold text-success mb-2"><i class="bi bi-shield-check me-1"></i> What Will Be Preserved:</h6>
                                <ul class="small text-success mb-0 ps-3">
                                    <li><strong>Super Admin account & login credentials</strong></li>
                                    <li>Core system roles & system permissions</li>
                                    <li>Master product categories & subcategories</li>
                                    <li>Master brands, sizes, colors & measurement units</li>
                                    <li>Core system settings & configurations</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="d-flex align-items-center text-primary mb-1">
                            <i class="bi bi-shield-lock-fill me-2 fs-5"></i>
                            <strong class="small">Automatic Rollback Guarantee:</strong>
                        </div>
                        <p class="text-muted small mb-0">The system will automatically generate a complete <code>pre_wipe_*.zip</code> safety backup snapshot before purging data. You can restore your data at any time from the backup list.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Super Admin Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password to authenticate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Type 'grm-delete-all-test-data' <span class="text-danger">*</span></label>
                            <input type="text" name="confirmation" class="form-control font-monospace" required placeholder="grm-delete-all-test-data">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-trash3-fill me-1"></i> Confirm & Delete All Test Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Email Backup Modal -->
<div class="modal fade" id="emailBackupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="<?= url('system/backup/email-latest') ?>">
                <?= CSRF::field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-envelope-arrow-up text-info me-2"></i>Email Database Backup Snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control fw-bold text-primary font-monospace" value="abiprasath6@gmail.com" required>
                        <div class="form-text small text-muted">Complete ZIP backup snapshot archive will be attached and delivered directly to this inbox.</div>
                    </div>

                    <div class="form-check form-switch bg-light p-3 rounded-3 border mb-3">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="fresh" id="freshBackupSwitch" value="1" checked>
                        <label class="form-check-label fw-semibold" for="freshBackupSwitch">Generate fresh real-time point-in-time snapshot now</label>
                    </div>

                    <div class="form-check form-switch bg-light p-3 rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="delete_local" id="deleteLocalSwitch" value="1" checked>
                        <label class="form-check-label fw-semibold" for="deleteLocalSwitch">
                            Zero Local Storage Mode (Purge server copy immediately after emailing)
                        </label>
                        <div class="small text-muted ms-4 mt-1">Leaves no large backup archives consuming local disk space on the server.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-info text-white rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-send-fill me-1"></i> Send Backup Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const restoreModalEl = document.getElementById('restoreModal');
    const restoreModal = restoreModalEl ? new bootstrap.Modal(restoreModalEl) : null;
    
    document.querySelectorAll('.btn-trigger-restore').forEach(btn => {
        btn.addEventListener('click', function() {
            const filename = this.getAttribute('data-filename');
            document.getElementById('restoreFilename').value = filename;
            document.getElementById('restoreFilenameDisplay').innerText = filename;
            if (restoreModal) restoreModal.show();
        });
    });

    // Export button click feedback
    document.querySelectorAll('a[href*="system/backup/export-"]').forEach(link => {
        link.addEventListener('click', function() {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show shadow-sm border-0 position-fixed top-0 start-50 translate-middle-x mt-4';
            alertDiv.style.zIndex = '9999';
            alertDiv.style.minWidth = '360px';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-success me-3" role="status"></div>
                    <div>
                        <strong>Full Database Backup Initiated!</strong>
                        <div class="small text-muted">Downloading whole backup archive & auto-emailing copy to <span class="text-primary font-monospace fw-bold">abiprasath6@gmail.com</span>...</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 400);
            }, 6000);
        });
    });
});
</script>
