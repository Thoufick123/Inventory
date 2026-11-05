<?php
// views/system/settings.php
$activeTab = $_GET['tab'] ?? 'company';
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-sliders text-primary me-2"></i>Unified System Settings Hub</h3>
            <p class="text-muted small mb-0">Manage enterprise store profile, tax details, operational switches, security rules, and alert policies.</p>
        </div>
    </div>

    <!-- Navigation Tabs Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs ms-3 border-0" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'company' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-pane">
                        <i class="bi bi-building me-2"></i>Company & Billing
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'switches' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="switches-tab" data-bs-toggle="tab" data-bs-target="#switches-pane">
                        <i class="bi bi-toggle-on me-2"></i>Master Switches
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'inventory' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-pane">
                        <i class="bi bi-boxes me-2"></i>Inventory Rules
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold py-3 border-0 <?= $activeTab === 'security' ? 'active text-primary border-bottom border-primary border-3' : 'text-muted' ?>" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-pane">
                        <i class="bi bi-shield-lock me-2"></i>Security Policies
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- TAB 1: COMPANY & BILLING -->
                <div class="tab-pane fade <?= $activeTab === 'company' ? 'show active' : '' ?>" id="company-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <input type="hidden" name="group" value="company">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shop me-2 text-primary"></i>Company Profile & Billing Details</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company / Brand Name</label>
                                <input type="text" name="settings[company_name]" class="form-control" value="<?= e($settings['company']['company_name'] ?? 'GRM Textiles') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tagline / Subtitle</label>
                                <input type="text" name="settings[company_tagline]" class="form-control" value="<?= e($settings['company']['company_tagline'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Support Email</label>
                                <input type="email" name="settings[company_email]" class="form-control" value="<?= e($settings['company']['company_email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Support Phone</label>
                                <input type="text" name="settings[company_phone]" class="form-control" value="<?= e($settings['company']['company_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">GSTIN Number</label>
                                <input type="text" name="settings[company_gstin]" class="form-control font-monospace" value="<?= e($settings['company']['company_gstin'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">PAN Number</label>
                                <input type="text" name="settings[company_pan]" class="form-control font-monospace" value="<?= e($settings['company']['company_pan'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Currency Symbol</label>
                                <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= e($settings['company']['currency_symbol'] ?? '₹') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Registered Office Address</label>
                                <textarea name="settings[company_address]" class="form-control" rows="2"><?= e($settings['company']['company_address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Invoice Footer Notes / Terms</label>
                                <textarea name="settings[invoice_footer]" class="form-control" rows="2"><?= e($settings['company']['invoice_footer'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Company Profile</button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: MASTER SWITCHES -->
                <div class="tab-pane fade <?= $activeTab === 'switches' ? 'show active' : '' ?>" id="switches-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <input type="hidden" name="group" value="switches">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-toggle2-on me-2 text-primary"></i>Operational Master Switches</h5>
                        
                        <div class="list-group list-group-flush mb-3">
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">System Maintenance Mode</div>
                                    <div class="small text-muted">Disables non-admin POS and shop access while performing database maintenance.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[maintenance_mode]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[maintenance_mode]" value="true" <?= ($settings['switches']['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Disable POS Guest Billing</div>
                                    <div class="small text-muted">Requires shop staff to record a valid customer phone number before completing sales.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[disable_pos_guest]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[disable_pos_guest]" value="true" <?= ($settings['switches']['disable_pos_guest'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Auto-Approve Production Programs</div>
                                    <div class="small text-muted">Automatically marks newly created production programs as APPROVED without manager sign-off.</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[auto_approve_program]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[auto_approve_program]" value="true" <?= ($settings['switches']['auto_approve_program'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark">Allow Negative Stock Movement</div>
                                    <div class="small text-muted">Permits inventory deduction even when recorded balance is 0 (Not recommended).</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="settings[allow_negative_stock]" value="false">
                                    <input class="form-check-input fs-4" type="checkbox" name="settings[allow_negative_stock]" value="true" <?= ($settings['switches']['allow_negative_stock'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Master Switches</button>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: INVENTORY RULES -->
                <div class="tab-pane fade <?= $activeTab === 'inventory' ? 'show active' : '' ?>" id="inventory-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <input type="hidden" name="group" value="inventory">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2 text-primary"></i>Stock & Inventory Threshold Rules</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Default Low Stock Alert Threshold (Units)</label>
                                <input type="number" name="settings[low_stock_threshold]" class="form-control" value="<?= e($settings['inventory']['low_stock_threshold'] ?? '10') ?>" min="1">
                                <div class="form-text">Triggers automated low-stock warnings when available inventory drops below this quantity.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Default Receiving Warehouse ID</label>
                                <input type="number" name="settings[default_warehouse_id]" class="form-control" value="<?= e($settings['inventory']['default_warehouse_id'] ?? '1') ?>">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="settings[audit_stock_movement]" value="false">
                                    <input class="form-check-input" type="checkbox" name="settings[audit_stock_movement]" value="true" id="auditStockCheck" <?= ($settings['inventory']['audit_stock_movement'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="auditStockCheck">Strict Stock Movement Audit Logging</label>
                                    <div class="form-text">Logs every individual stock increase, decrease, reserve, and transfer in `stock_transactions`.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Inventory Rules</button>
                        </div>
                    </form>
                </div>

                <!-- TAB 4: SECURITY POLICIES -->
                <div class="tab-pane fade <?= $activeTab === 'security' ? 'show active' : '' ?>" id="security-pane">
                    <form method="POST" action="<?= url('system/settings/update') ?>">
                        <input type="hidden" name="group" value="security">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Security & Authentication Policies</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Session Timeout Duration (Minutes)</label>
                                <input type="number" name="settings[session_timeout_mins]" class="form-control" value="<?= e($settings['security']['session_timeout_mins'] ?? '60') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Max Invalid Login Attempts</label>
                                <input type="number" name="settings[max_login_attempts]" class="form-control" value="<?= e($settings['security']['max_login_attempts'] ?? '5') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Lockout Duration (Minutes)</label>
                                <input type="number" name="settings[lockout_duration_mins]" class="form-control" value="<?= e($settings['security']['lockout_duration_mins'] ?? '15') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input type="hidden" name="settings[force_password_change]" value="false">
                                    <input class="form-check-input" type="checkbox" name="settings[force_password_change]" value="true" id="forcePassCheck" <?= ($settings['security']['force_password_change'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="forcePassCheck">Force Password Change on Admin Reset</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Security Policies</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
