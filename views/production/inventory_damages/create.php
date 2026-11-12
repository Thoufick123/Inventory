<div class="d-flex align-items-center gap-2 mb-4">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('production/inventory-damages') ?>" title="Back to damage cases"><i class="bi bi-arrow-left"></i></a>
    <div><h2 class="h4 mb-0 fw-bold">Report Finished Goods Damage</h2><small class="text-muted">Scan QC-passed or inwarded concern pieces currently available in Production.</small></div>
</div>

<form id="productionDamageForm" enctype="multipart/form-data">
    <?= CSRF::input() ?>
    <input type="hidden" name="items" id="damageItems" value="[]">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label for="damageTagInput" class="form-label fw-semibold">Piece QR / Barcode</label>
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-qr-code-scan"></i></span>
                <input id="damageTagInput" class="form-control font-monospace" autocomplete="off" placeholder="Scan or enter a production tag">
                <button id="lookupDamageTag" class="btn btn-primary" type="button"><i class="bi bi-plus-lg me-1"></i>Add piece</button>
            </div>
            <div id="damageScanMessage" class="small mt-2" role="status" aria-live="polite"></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Scanned pieces</strong><span id="damagePieceCount" class="badge bg-danger-subtle text-danger">0 pieces</span></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>QR Tag</th><th>Product / Variant</th><th>QC Batch / Program</th><th class="text-end">Value</th><th></th></tr></thead><tbody id="damagePieceRows"><tr><td colspan="5" class="text-center text-muted py-4">Scan a piece to begin.</td></tr></tbody></table></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label for="damageStage" class="form-label fw-semibold">Where found</label><select class="form-select" name="source_stage" id="damageStage" required><?php foreach ($sourceStages as $stage): ?><option value="<?= e($stage) ?>"><?= e(str_replace('_', ' ', $stage)) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-8"><label for="damageReason" class="form-label fw-semibold">Damage reason</label><input class="form-control" name="damage_reason" id="damageReason" maxlength="255" required placeholder="e.g. stitching defect, stain, packaging damage"></div>
                <div class="col-md-8"><label for="damageNotes" class="form-label fw-semibold">Inspection notes</label><textarea class="form-control" name="defect_notes" id="damageNotes" rows="3" placeholder="Describe the defect and any handling details"></textarea></div>
                <div class="col-md-4"><label for="damagePhoto" class="form-label fw-semibold">Evidence photo <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" type="file" name="photo" id="damagePhoto" accept="image/jpeg,image/png,image/webp"></div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="<?= url('production/inventory-damages') ?>">Cancel</a><button type="submit" id="saveDamageCases" class="btn btn-danger"><i class="bi bi-shield-exclamation me-1"></i>Quarantine scanned pieces</button></div>
</form>

<script>
(() => {
    const form = document.getElementById('productionDamageForm');
    const tagInput = document.getElementById('damageTagInput');
    const rows = document.getElementById('damagePieceRows');
    const message = document.getElementById('damageScanMessage');
    const pieces = new Map();

    function showMessage(text, kind = 'muted') {
        message.className = `small mt-2 text-${kind}`;
        message.textContent = text;
    }

    function renderPieces() {
        document.getElementById('damagePieceCount').textContent = `${pieces.size} piece${pieces.size === 1 ? '' : 's'}`;
        document.getElementById('damageItems').value = JSON.stringify([...pieces.values()].map(piece => ({tag_code: piece.tag_code})));
        if (!pieces.size) {
            rows.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Scan a piece to begin.</td></tr>';
            return;
        }
        rows.innerHTML = [...pieces.entries()].map(([tag, piece]) => {
            const sourceTitle = piece.qc_batch_number || piece.location_label || 'Inward Concern Stock';
            const sourceMeta = piece.program_number || (piece.source_kind === 'INWARD' ? 'Inward Stock' : '');
            return `<tr>
            <td class="font-monospace fw-semibold">${escapeHtml(tag)}</td>
            <td>${escapeHtml(piece.product_name)}<small class="d-block text-muted">${escapeHtml(piece.size_name || 'Std')} / ${escapeHtml(piece.color_name || 'Std')}</small></td>
            <td>${escapeHtml(sourceTitle)}<small class="d-block text-muted">${escapeHtml(sourceMeta)}</small></td>
            <td class="text-end">₹${Number(piece.unit_value || 0).toFixed(2)}</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-tag="${escapeHtml(tag)}" title="Remove piece"><i class="bi bi-x-lg"></i></button></td>
        </tr>`;
        }).join('');
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    async function lookupTag() {
        const tag = tagInput.value.trim();
        if (!tag) return;
        showMessage('Checking Production inventory…');
        try {
            const response = await fetch(`<?= url('api/production/inventory-damages/scan') ?>?code=${encodeURIComponent(tag)}`, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'This piece is not available.');
            const piece = payload.piece;
            const normalized = piece.tag_code.toUpperCase();
            if (pieces.has(normalized)) throw new Error('This piece is already in the scan list.');
            pieces.set(normalized, piece);
            tagInput.value = '';
            renderPieces();
            const sourceNote = piece.qc_batch_number ? `QC batch ${piece.qc_batch_number}` : (piece.location_label || 'Inward stock');
            showMessage(`${normalized} added from ${sourceNote}.`, 'success');
            tagInput.focus();
        } catch (error) {
            showMessage(error.message, 'danger');
            tagInput.select();
        }
    }

    document.getElementById('lookupDamageTag').addEventListener('click', lookupTag);
    tagInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); lookupTag(); }
    });
    rows.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-tag]');
        if (button) { pieces.delete(button.dataset.removeTag); renderPieces(); }
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!pieces.size) { showMessage('Scan at least one available piece.', 'danger'); return; }
        const button = document.getElementById('saveDamageCases');
        button.disabled = true;
        showMessage('Saving damage cases…');
        try {
            const response = await fetch('<?= url('production/inventory-damages/store') ?>', {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Could not save damage cases.');
            window.location.href = <?= json_encode(url('production/inventory-damages')) ?>;
        } catch (error) {
            button.disabled = false;
            showMessage(error.message, 'danger');
        }
    });
})();
</script>