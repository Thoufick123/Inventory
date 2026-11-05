const existingSuppliersData = window.existingSuppliersData || [];
const preselectedSellerId = Number(window.preselectedSellerId || 0);
const isEditMode = window.location.search.includes('mode=edit');
let currentSupplierMode = 'existing';
let currentBrandMode = 'existing';

document.addEventListener('DOMContentLoaded', function () {
    const newSupplierNameInput = document.getElementById('newSupplierNameInput');
    const supplierCompanyInput = document.getElementById('supplierCompany');
    const newBrandNameInput = document.getElementById('newBrandNameInput');

    [newSupplierNameInput, supplierCompanyInput, newBrandNameInput].forEach(function (input) {
        if (!input) return;
        input.addEventListener('input', function () {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = input.value.toUpperCase();
            if (start !== null && end !== null) input.setSelectionRange(start, end);

            if (input === newSupplierNameInput) {
                const hiddenName = document.getElementById('supplierName');
                if (hiddenName) hiddenName.value = input.value.trim();
            }
        });
    });

    const searchInput = document.getElementById('supplierSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterSupplierSuggestions);
        searchInput.addEventListener('focus', filterSupplierSuggestions);
    }

    document.querySelectorAll('.supplier-suggestion-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const supplierId = this.dataset.supplierId;
            const label = this.dataset.supplierLabel || this.textContent.trim();
            onSupplierDropdownChange(supplierId);
            const input = document.getElementById('supplierSearchInput');
            if (input) input.value = label;
            const box = document.getElementById('supplierSuggestionsBox');
            if (box) box.classList.add('d-none');
        });
    });

    if (preselectedSellerId > 0) {
        setSupplierMode('existing');
        onSupplierDropdownSelect(preselectedSellerId);

        if (isEditMode) {
            const input = document.getElementById('supplierSearchInput');
            const suggestionsBox = document.getElementById('supplierSuggestionsBox');
            if (input) {
                const selectedSupplier = existingSuppliersData.find(function (s) {
                    return Number(s.id) === Number(preselectedSellerId);
                });
                input.value = selectedSupplier ? (selectedSupplier.company || selectedSupplier.name || '') : '';
                input.readOnly = true;
                input.placeholder = 'Editing selected supplier profile';
                input.classList.add('bg-light');
            }
            if (suggestionsBox) {
                suggestionsBox.classList.add('d-none');
            }

            const submitBtn = document.getElementById('btnSubmitPhase1');
            if (submitBtn) {
                submitBtn.innerHTML = 'Update Supplier &amp; Continue to Products (Phase 2) <i class="bi bi-arrow-right ms-1"></i>';
            }
        }
    } else if (existingSuppliersData.length > 0) {
        setSupplierMode('existing');
        onSupplierDropdownSelect(existingSuppliersData[0].id);
    } else {
        setSupplierMode('new');
    }
});

function setSupplierMode(mode) {
    currentSupplierMode = mode;
    const dropWrap = document.getElementById('supplierDropdownWrapper');
    const textWrap = document.getElementById('supplierTextInputWrapper');
    const toggleBtn = document.getElementById('btnToggleSupplierMode');
    const badgeExisting = document.getElementById('badgeExistingActive');
    const badgeNew = document.getElementById('badgeNewActive');
    const banner = document.getElementById('existingSupplierBanner');
    const btnSubmit = document.getElementById('btnSubmitPhase1');
    const hiddenId = document.getElementById('existingSupplierId');
    const supplierNameInput = document.getElementById('supplierName');
    const newNameInput = document.getElementById('newSupplierNameInput');

    if (mode === 'new') {
        if (dropWrap) dropWrap.classList.add('d-none');
        if (textWrap) textWrap.classList.remove('d-none');
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="bi bi-people-fill text-primary me-1"></i>Pick Existing Supplier';
        }
        if (badgeExisting) badgeExisting.classList.add('d-none');
        if (badgeNew) badgeNew.classList.remove('d-none');
        if (banner) {
            banner.classList.add('d-none');
            banner.classList.remove('d-flex');
        }
        if (hiddenId) hiddenId.value = '';
        clearFormInputs();
        if (supplierNameInput) supplierNameInput.value = newNameInput ? (newNameInput.value.trim() || '') : '';
        if (btnSubmit) {
            btnSubmit.innerHTML = 'Save New Supplier &amp; Continue to Products (Phase 2) <i class="bi bi-arrow-right ms-1"></i>';
        }
        if (newNameInput) newNameInput.focus();
    } else {
        if (dropWrap) dropWrap.classList.remove('d-none');
        if (textWrap) textWrap.classList.add('d-none');
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="bi bi-person-plus-fill text-success me-1"></i>+ Add New Supplier';
        }

        const preferredId = preselectedSellerId > 0 ? preselectedSellerId : (existingSuppliersData[0] ? Number(existingSuppliersData[0].id) : 0);
        if (preferredId > 0) {
            onSupplierDropdownSelect(preferredId);
        } else if (existingSuppliersData.length > 0) {
            onSupplierDropdownSelect(existingSuppliersData[0].id);
        }
    }
}

function toggleSupplierModeDirect() {
    if (currentSupplierMode === 'existing') {
        setSupplierMode('new');
    } else {
        setSupplierMode('existing');
    }
}

function filterSupplierSuggestions() {
    const searchInput = document.getElementById('supplierSearchInput');
    const box = document.getElementById('supplierSuggestionsBox');
    if (!searchInput || !box) return;

    const query = searchInput.value.trim().toLowerCase();
    const items = Array.from(document.querySelectorAll('.supplier-suggestion-item'));
    let visible = 0;

    items.forEach(function (item) {
        const label = (item.dataset.supplierLabel || item.textContent || '').toLowerCase();
        const shouldShow = query === '' || label.includes(query);
        item.classList.toggle('d-none', !shouldShow);
        if (shouldShow) visible++;
    });

    box.classList.toggle('d-none', visible === 0 && query !== '');
    if (query === '') {
        box.classList.remove('d-none');
    }
}

function onSupplierDropdownChange(val) {
    if (val === '__NEW__') {
        setSupplierMode('new');
        return;
    }
    onSupplierDropdownSelect(val);
}

function onSupplierDropdownSelect(id) {
    const hiddenIdInput = document.getElementById('existingSupplierId');
    const supplierNameInput = document.getElementById('supplierName');
    const banner = document.getElementById('existingSupplierBanner');
    const bannerName = document.getElementById('bannerSupplierName');
    const badgeExisting = document.getElementById('badgeExistingActive');
    const badgeNew = document.getElementById('badgeNewActive');
    const btnSubmit = document.getElementById('btnSubmitPhase1');

    if (!id || id === '__NEW__') {
        const searchInput = document.getElementById('supplierSearchInput');
        const suggestionsBox = document.getElementById('supplierSuggestionsBox');
        if (searchInput) {
            searchInput.readOnly = false;
            searchInput.value = '';
            searchInput.placeholder = 'Type supplier name or phone number...';
            searchInput.classList.remove('bg-light');
        }
        if (suggestionsBox) {
            suggestionsBox.classList.remove('d-none');
        }

        if (hiddenIdInput) hiddenIdInput.value = '';
        if (supplierNameInput) supplierNameInput.value = '';
        clearFormInputs();
        if (banner) {
            banner.classList.add('d-none');
            banner.classList.remove('d-flex');
        }
        if (badgeExisting) badgeExisting.classList.add('d-none');
        if (badgeNew) badgeNew.classList.remove('d-none');
        if (btnSubmit) btnSubmit.innerHTML = 'Save Supplier &amp; Continue to Products (Phase 2) <i class="bi bi-arrow-right ms-1"></i>';
        return;
    }

    const supplier = existingSuppliersData.find(function (s) {
        return parseInt(s.id, 10) === parseInt(id, 10);
    });
    if (!supplier) return;
    renderExistingBillDocument(isEditMode ? supplier.document_url : '');

    const searchInput = document.getElementById('supplierSearchInput');
    const suggestionsBox = document.getElementById('supplierSuggestionsBox');
    if (searchInput) {
        searchInput.value = supplier.company || supplier.name || '';
        searchInput.readOnly = !!isEditMode;
        searchInput.placeholder = isEditMode ? 'Editing selected supplier profile' : 'Type supplier name or phone number...';
        searchInput.classList.toggle('bg-light', !!isEditMode);
    }
    if (suggestionsBox) {
        suggestionsBox.classList.toggle('d-none', !!isEditMode);
    }

    if (hiddenIdInput) hiddenIdInput.value = supplier.id;
    if (supplierNameInput) supplierNameInput.value = supplier.name || '';

    document.getElementById('supplierCompany').value = supplier.company || '';
    document.getElementById('supplierPhone').value = normalizeSupplierPhone(supplier.phone || '');
    document.getElementById('supplierEmail').value = supplier.email || '';
    document.getElementById('supplierGst').value = supplier.gst_number || '';

    const sellerTypeSelect = document.querySelector('select[name="seller_type"]');
    if (sellerTypeSelect && supplier.seller_type) {
        sellerTypeSelect.value = supplier.seller_type;
    }

    const addressTextarea = document.querySelector('textarea[name="address"]');
    if (addressTextarea) addressTextarea.value = supplier.address || '';

    const cityInput = document.querySelector('input[name="city"]');
    if (cityInput) cityInput.value = supplier.city || '';

    const stateInput = document.querySelector('input[name="state"]');
    if (stateInput) stateInput.value = supplier.state || 'Tamil Nadu';

    const termsInput = document.querySelector('input[name="payment_terms"]');
    if (termsInput) termsInput.value = supplier.payment_terms || '';

    const matInput = document.querySelector('input[name="material_type"]');
    if (matInput) matInput.value = supplier.material_type || 'Third-Party Garments & Products';

    const brandSel = document.getElementById('brandSelect');
    const newBrandInput = document.getElementById('newBrandNameInput');
    if (supplier.brand_id && brandSel) {
        setBrandMode('existing');
        brandSel.value = supplier.brand_id;
    } else if (supplier.brand_name && brandSel) {
        setBrandMode('existing');
        let matched = false;
        for (let i = 0; i < brandSel.options.length; i++) {
            if ((brandSel.options[i].text || '').trim().toUpperCase() === supplier.brand_name.trim().toUpperCase()) {
                brandSel.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) brandSel.value = '';
    } else {
        if (brandSel) brandSel.value = '';
        if (newBrandInput) newBrandInput.value = '';
    }

    const displayName = supplier.company ? supplier.company + ' (' + supplier.name + ')' : supplier.name;
    if (bannerName) bannerName.textContent = displayName;
    if (banner) {
        banner.classList.remove('d-none');
        banner.classList.add('d-flex');
    }
    if (badgeExisting) badgeExisting.classList.remove('d-none');
    if (badgeNew) badgeNew.classList.add('d-none');

    if (btnSubmit) {
        btnSubmit.innerHTML = 'Continue with <strong>' + escapeHtml(displayName) + '</strong> to Products (Phase 2) <i class="bi bi-arrow-right ms-1"></i>';
    }
}

function setBrandMode(mode) {
    currentBrandMode = mode;
    const dropWrap = document.getElementById('brandDropdownWrapper');
    const textWrap = document.getElementById('brandTextInputWrapper');
    const toggleBtn = document.getElementById('btnToggleBrandMode');
    const brandSelect = document.getElementById('brandSelect');
    const newBrandInput = document.getElementById('newBrandNameInput');

    if (mode === 'new') {
        if (dropWrap) dropWrap.classList.add('d-none');
        if (textWrap) textWrap.classList.remove('d-none');
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="bi bi-list-ul text-primary me-1"></i>Pick Existing Brand';
        }
        if (brandSelect) brandSelect.value = '__NEW__';
        if (newBrandInput) newBrandInput.focus();
    } else {
        if (dropWrap) dropWrap.classList.remove('d-none');
        if (textWrap) textWrap.classList.add('d-none');
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="bi bi-plus-circle-fill text-success me-1"></i>+ Add New Brand';
        }
        if (brandSelect && brandSelect.value === '__NEW__') {
            brandSelect.value = '';
        }
    }
}

function toggleBrandModeDirect() {
    if (currentBrandMode === 'existing') {
        setBrandMode('new');
    } else {
        setBrandMode('existing');
    }
}

function onBrandSelectChange(val) {
    if (val === '__NEW__') {
        setBrandMode('new');
    }
}

function onNewSupplierNameInput(val) {
    const input = document.getElementById('supplierName');
    if (input) input.value = (val || '').trim().toUpperCase();
}

function clearExistingSupplierSelection() {
    const hiddenId = document.getElementById('existingSupplierId');
    if (hiddenId) hiddenId.value = '';
    setSupplierMode('new');
}

function clearFormInputs() {
    renderExistingBillDocument('');
    const supplierName = document.getElementById('supplierName');
    if (supplierName) supplierName.value = '';
    const newName = document.getElementById('newSupplierNameInput');
    if (newName) newName.value = '';

    const companyInput = document.getElementById('supplierCompany');
    if (companyInput) companyInput.value = '';

    const phoneInput = document.getElementById('supplierPhone');
    if (phoneInput) phoneInput.value = '';

    const emailInput = document.getElementById('supplierEmail');
    if (emailInput) emailInput.value = '';

    const gstInput = document.getElementById('supplierGst');
    if (gstInput) gstInput.value = '';

    const brandSel = document.getElementById('brandSelect');
    if (brandSel) brandSel.value = '';
    const newBrandInput = document.getElementById('newBrandNameInput');
    if (newBrandInput) newBrandInput.value = '';
    setBrandMode('existing');

    const addressTextarea = document.querySelector('textarea[name="address"]');
    if (addressTextarea) addressTextarea.value = '';

    const cityInput = document.querySelector('input[name="city"]');
    if (cityInput) cityInput.value = '';

    const termsInput = document.querySelector('input[name="payment_terms"]');
    if (termsInput) termsInput.value = '';
}

function renderExistingBillDocument(url) {
    const container = document.getElementById('existingBillDocument');
    const preview = document.getElementById('existingBillPreview');
    const link = document.getElementById('existingBillLink');
    if (!container || !preview || !link) return;

    preview.innerHTML = '';
    const requiredMarker = document.getElementById('billRequiredMarker');
    const uploadHelp = document.getElementById('billUploadHelp');
    if (!url) {
        container.classList.add('d-none');
        if (requiredMarker) requiredMarker.classList.remove('d-none');
        if (uploadHelp) uploadHelp.textContent = 'Required to continue. Supports JPG, PNG, WEBP, or PDF invoices (max 10MB).';
        return;
    }
    if (requiredMarker) requiredMarker.classList.add('d-none');
    if (uploadHelp) uploadHelp.textContent = 'Existing bill will be reused. Upload a new bill only to replace it.';

    link.href = url;
    preview.className = 'd-flex align-items-center justify-content-center rounded border bg-light flex-shrink-0';
    preview.style.width = '64px';
    preview.style.height = '64px';
    const isImage = /\.(jpe?g|png|webp|gif)(?:$|[?#])/i.test(url) || (/\/image\/upload\//i.test(url) && !/\.pdf(?:$|[?#])/i.test(url));
    if (isImage) {
        const image = document.createElement('img');
        image.src = url;
        image.alt = 'Existing supplier bill';
        image.className = 'rounded';
        image.style.maxWidth = '64px';
        image.style.maxHeight = '64px';
        image.style.objectFit = 'contain';
        preview.appendChild(image);
    } else {
        const icon = document.createElement('i');
        icon.className = 'bi bi-file-earmark-pdf fs-2 text-danger';
        preview.appendChild(icon);
    }
    container.classList.remove('d-none');
}

function handleBillImageSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    const fileName = document.getElementById('billFileName');
    if (fileName) fileName.textContent = file.name;
    const placeholder = document.getElementById('billUploadPlaceholder');
    if (placeholder) placeholder.classList.add('d-none');
    const previewBox = document.getElementById('billImagePreviewBox');
    if (previewBox) previewBox.classList.remove('d-none');

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const previewImg = document.getElementById('billPreviewImg');
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
            }
        };
        reader.readAsDataURL(file);
    } else {
        const previewImg = document.getElementById('billPreviewImg');
        if (previewImg) previewImg.classList.add('d-none');
    }
}

function removeBillImage() {
    const input = document.getElementById('billImageInput');
    if (input) input.value = '';
    const previewImg = document.getElementById('billPreviewImg');
    if (previewImg) previewImg.src = '';
    const previewBox = document.getElementById('billImagePreviewBox');
    if (previewBox) previewBox.classList.add('d-none');
    const placeholder = document.getElementById('billUploadPlaceholder');
    if (placeholder) placeholder.classList.remove('d-none');
}

function normalizeSupplierPhone(phone) {
    if (!phone) return '';
    const digits = String(phone).replace(/\D/g, '');
    if (digits.length === 10) return digits;
    if (digits.length > 10 && digits.startsWith('91')) return digits.slice(2);
    return digits;
}

function handlePhase1Submit(event) {
    const companyInput = document.getElementById('supplierCompany');
    if (companyInput) companyInput.value = companyInput.value.toUpperCase();

    const billImageInput = document.getElementById('billImageInput');
    const selectedSupplierId = Number(document.getElementById('existingSupplierId')?.value || 0);
    const selectedSupplier = existingSuppliersData.find(function (supplier) {
        return Number(supplier.id) === selectedSupplierId;
    });
    const hasExistingBill = isEditMode && !!(selectedSupplier && selectedSupplier.document_url);
    if ((!billImageInput || !billImageInput.files || billImageInput.files.length === 0) && !hasExistingBill) {
        event.preventDefault();
        const billDropzone = document.getElementById('billDropzone');
        if (billDropzone) {
            billDropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        showValidationAlert('Please upload the supplier bill or invoice before continuing.');
        return false;
    }

    if (billImageInput.files[0].size > 10 * 1024 * 1024) {
        event.preventDefault();
        const billDropzone = document.getElementById('billDropzone');
        if (billDropzone) {
            billDropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        showValidationAlert('The bill or invoice file must be 10MB or smaller.');
        return false;
    }

    const supplierNameInput = document.getElementById('supplierName');
    if (supplierNameInput) supplierNameInput.value = (supplierNameInput.value || '').trim();

    const phoneInput = document.getElementById('supplierPhone');
    const phoneValue = phoneInput ? phoneInput.value.trim() : '';
    const phoneDigits = phoneValue.replace(/\D/g, '');
    if (phoneValue && !/^[6-9]\d{9}$/.test(phoneDigits)) {
        event.preventDefault();
        if (phoneInput) phoneInput.focus();
        showValidationAlert('Enter a valid 10-digit Indian mobile number.');
        return false;
    }

    const emailInput = document.getElementById('supplierEmail');
    const emailValue = emailInput ? emailInput.value.trim() : '';
    if (emailValue && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
        event.preventDefault();
        if (emailInput) emailInput.focus();
        showValidationAlert('Enter a valid email address.');
        return false;
    }

    const gstInput = document.getElementById('supplierGst');
    const gstValue = gstInput ? gstInput.value.trim().toUpperCase() : '';
    if (gstInput) gstInput.value = gstValue;
    if (gstValue && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(gstValue)) {
        event.preventDefault();
        if (gstInput) gstInput.focus();
        showValidationAlert('Enter a valid 15-character GSTIN.');
        return false;
    }

    const normalizedPhone = normalizeSupplierPhone(phoneValue);
    const selectedExistingId = Number(document.getElementById('existingSupplierId')?.value || 0);
    if (normalizedPhone) {
        const duplicate = existingSuppliersData.some(function (supplier) {
            const existingPhone = normalizeSupplierPhone(supplier.phone || '');
            const supplierId = Number(supplier.id);
            return existingPhone && existingPhone === normalizedPhone && supplierId !== selectedExistingId;
        });

        if (duplicate) {
            event.preventDefault();
            if (phoneInput) phoneInput.focus();
            showValidationAlert('This phone number is already registered to an existing supplier. Duplicate supplier creation is not allowed.');
            return false;
        }
    }

    if (currentSupplierMode === 'new') {
        const newName = document.getElementById('newSupplierNameInput').value.trim();
        if (!newName || newName.length < 2) {
            event.preventDefault();
            const newNameInput = document.getElementById('newSupplierNameInput');
            if (newNameInput) newNameInput.focus();
            showValidationAlert('Supplier Contact / Name is mandatory (minimum 2 characters).');
            return false;
        }
        if (supplierNameInput) supplierNameInput.value = newName.toUpperCase();
        const companyInput = document.getElementById('supplierCompany');
        if (companyInput) companyInput.value = companyInput.value.toUpperCase();
        const hiddenId = document.getElementById('existingSupplierId');
        if (hiddenId) hiddenId.value = '';
    } else {
        const selectedSupplierId = Number(document.getElementById('existingSupplierId')?.value || 0);
        if (!selectedSupplierId) {
            event.preventDefault();
            showValidationAlert('Please select an existing supplier or switch to "+ Add New Supplier".');
            return false;
        }
        const supplier = existingSuppliersData.find(function (s) {
            return parseInt(s.id, 10) === parseInt(selectedSupplierId, 10);
        });
        if (!supplier) {
            event.preventDefault();
            showValidationAlert('Selected supplier was not found.');
            return false;
        }
        if (supplierNameInput) supplierNameInput.value = supplier.name;
        const hiddenId = document.getElementById('existingSupplierId');
        if (hiddenId) hiddenId.value = supplier.id;
    }

    const brandSel = document.getElementById('brandSelect');
    const brandVal = brandSel ? brandSel.value : '';
    const newBrandInput = document.getElementById('newBrandNameInput');
    const newBrandVal = newBrandInput ? newBrandInput.value.trim().toUpperCase() : '';
    if (newBrandInput) newBrandInput.value = newBrandVal;

    if (currentBrandMode === 'new' || brandVal === '__NEW__') {
        if (!newBrandVal || newBrandVal.length < 2) {
            event.preventDefault();
            if (newBrandInput) newBrandInput.focus();
            showValidationAlert('Brand Name is mandatory (minimum 2 characters).');
            return false;
        }
    } else {
        if (!brandVal && !newBrandVal) {
            event.preventDefault();
            if (brandSel) brandSel.focus();
            showValidationAlert('Please select a Brand Name from the dropdown or click "+ Add New Brand".');
            return false;
        }
    }

    const btn = document.getElementById('btnSubmitPhase1');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Supplier Details...';
    }
    return true;
}

function showValidationAlert(msg) {
    const alertBox = document.getElementById('validationAlert');
    const alertText = document.getElementById('validationAlertText');
    if (alertText) alertText.textContent = msg;
    if (alertBox) {
        alertBox.classList.remove('d-none');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
