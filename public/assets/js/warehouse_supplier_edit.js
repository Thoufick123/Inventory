function handleExistingRowQRModeChange(select) {
    const row = select.closest('tr');
    if (!row) return;
    const seriesBox = row.querySelector('.existing-series-box');
    if (select.value === 'SERIES_BATCH') {
        if (seriesBox) seriesBox.classList.remove('d-none');
    } else {
        if (seriesBox) seriesBox.classList.add('d-none');
    }
}

function handleNewRowQRModeChange(select) {
    const row = select.closest('tr');
    if (!row) return;
    const seriesBox = row.querySelector('.row-series-box');
    if (select.value === 'SERIES_BATCH') {
        if (seriesBox) seriesBox.classList.remove('d-none');
        const skuInput = row.querySelector('.sku-input');
        const startInput = row.querySelector('.series-start-input');
        const endInput = row.querySelector('.series-end-input');
        let sku = (skuInput && skuInput.value ? skuInput.value.trim() : '');
        if (!sku) {
            const name = row.querySelector('.item-name-input')?.value || document.getElementById('supplierCompanyInput')?.value || 'ITEM';
            const color = row.querySelector('.color-input')?.value || '';
            const size = row.querySelector('.size-input')?.value || '';
            sku = generateCleanSku(name, color, size);
            if (skuInput) skuInput.value = sku;
        }
        if (startInput && !startInput.value.trim()) startInput.value = sku + '-001';
        if (endInput && !endInput.value.trim()) endInput.value = sku + '-010';
    } else {
        if (seriesBox) seriesBox.classList.add('d-none');
    }
}

function generateCleanSku(name, color, size) {
    name = (name || '').trim();
    color = (color || '').trim();
    size = (size || '').trim();
    const cleanName = name.replace(/[^A-Za-z0-9]/g, '').substring(0, 5).toUpperCase() || 'ITEM';
    const cleanColor = color ? '-' + color.replace(/[^A-Za-z0-9]/g, '').substring(0, 3).toUpperCase() : '';
    const cleanSize = size ? '-' + size.replace(/[^A-Za-z0-9]/g, '').substring(0, 4).toUpperCase() : '';
    const rand = Math.floor(100 + Math.random() * 900);
    return 'TP-' + cleanName + cleanColor + cleanSize + '-' + rand;
}

function autoGenerateNewSku(btn) {
    const row = btn.closest('tr');
    const name = row.querySelector('.item-name-input')?.value || document.getElementById('supplierCompanyInput')?.value || 'ITEM';
    const color = row.querySelector('.color-input')?.value || '';
    const size = row.querySelector('.size-input')?.value || '';
    const skuInput = row.querySelector('.sku-input');
    if (skuInput) skuInput.value = generateCleanSku(name, color, size);
}

function handleNewProductNameInput(input) {
    const row = input.closest('tr');
    const skuInput = row.querySelector('.sku-input');
    if (skuInput && !skuInput.value.trim()) {
        const color = row.querySelector('.color-input')?.value || '';
        const size = row.querySelector('.size-input')?.value || '';
        const newSku = generateCleanSku(input.value, color, size);
        skuInput.value = newSku;
    }
}

function handleNewVariantPropInput(input) {
    const row = input.closest('tr');
    const skuInput = row.querySelector('.sku-input');
    if (skuInput && (!skuInput.value.trim() || skuInput.value.startsWith('TP-'))) {
        const name = row.querySelector('.item-name-input')?.value || document.getElementById('supplierCompanyInput')?.value || 'ITEM';
        const color = row.querySelector('.color-input')?.value || '';
        const size = row.querySelector('.size-input')?.value || '';
        skuInput.value = generateCleanSku(name, color, size);
    }
}

function addNewProductRow() {
    const placeholder = document.getElementById('noNewRowsPlaceholder');
    if (placeholder) placeholder.style.display = 'none';

    const template = document.getElementById('newProductRowTemplate');
    const tbody = document.getElementById('newProductsTableBody');
    const clone = template.content.cloneNode(true);
    tbody.appendChild(clone);
    updateNewRowNumbers();
}

function removeNewProductRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    updateNewRowNumbers();
    const rows = document.querySelectorAll('#newProductsTableBody tr.new-product-row');
    if (rows.length === 0) {
        const placeholder = document.getElementById('noNewRowsPlaceholder');
        if (placeholder) placeholder.style.display = '';
    }
}

function updateNewRowNumbers() {
    const rows = document.querySelectorAll('#newProductsTableBody tr.new-product-row');
    rows.forEach((row, idx) => {
        const numEl = row.querySelector('.new-row-num');
        if (numEl) numEl.textContent = idx + 1;
    });
}
