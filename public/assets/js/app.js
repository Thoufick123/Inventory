/**
 * GRM Inventory — Core JavaScript
 * Sidebar toggle, AJAX helpers, flash messages, and utilities.
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // SIDEBAR TOGGLE
    // ============================================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => {
            sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    // ============================================
    // SUBMENU TOGGLE
    // ============================================
    document.querySelectorAll('.has-submenu > .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            if (submenu) {
                submenu.classList.toggle('show');
            }
            // Close other submenus
            document.querySelectorAll('.submenu.show').forEach(sm => {
                if (sm !== submenu) sm.classList.remove('show');
            });
        });
    });

    // ============================================
    // FLASH MESSAGE AUTO DISMISS
    // ============================================
    document.querySelectorAll('.flash-alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // ============================================
    // CONFIRM DIALOGS
    // ============================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ============================================
    // CSRF TOKEN FOR AJAX
    // ============================================
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // ============================================
    // AJAX HELPER
    // ============================================
    window.grm = {
        csrfToken: csrfToken,

        /**
         * Make an AJAX request.
         */
        ajax: async function(url, options = {}) {
            const defaults = {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            };

            if (options.method === 'POST' && options.body && !(options.body instanceof FormData)) {
                defaults.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const config = { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } };

            try {
                const response = await fetch(url, config);
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Request failed');
                }

                return data;
            } catch (error) {
                console.error('AJAX Error:', error);
                throw error;
            }
        },

        /**
         * Show a toast notification.
         */
        toast: function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} flash-alert position-fixed`;
            toast.style.cssText = 'top: 1rem; right: 1rem; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
                bsAlert.close();
            }, 4000);
        },

        /**
         * Format currency (INR).
         */
        formatCurrency: function(amount) {
            return '₹' + parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        /**
         * Debounce function for search inputs.
         */
        debounce: function(func, wait = 300) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    };

    // ============================================
    // BOOTSTRAP FORM VALIDATION
    // ============================================
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });

    // ============================================
    // NOTIFICATION POLLING (every 60 seconds)
    // ============================================
    function checkNotifications() {
        const badge = document.getElementById('notifBadge');
        if (!badge) return;

        grm.ajax(window.location.origin + '/grm-inventory/api/notifications/unread')
            .then(data => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(() => {}); // Silently fail
    }

    // Check notifications every 60 seconds
    setInterval(checkNotifications, 60000);
    // Initial check after page load
    setTimeout(checkNotifications, 2000);

    // ============================================
    // REAL-TIME SESSION & ACTIVE STATUS HEARTBEAT
    // ============================================
    const authCheckMeta = document.querySelector('meta[name="auth-check-url"]');
    const loginMeta = document.querySelector('meta[name="login-url"]');
    const authCheckUrl = authCheckMeta ? authCheckMeta.getAttribute('content') : null;
    const loginUrl = loginMeta ? loginMeta.getAttribute('content') : '/login';

    if (authCheckUrl) {
        let isCheckingAuth = false;
        async function verifyUserStatus() {
            if (isCheckingAuth) return;
            isCheckingAuth = true;
            try {
                const res = await fetch(authCheckUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.status === 401 || res.status === 403) {
                    window.location.href = loginUrl;
                    return;
                }
                const data = await res.json();
                if (data && data.active === false) {
                    window.location.href = data.redirect || loginUrl;
                }
            } catch (err) {
                // Ignore transient network errors
            } finally {
                isCheckingAuth = false;
            }
        }

        // Real-time heartbeat check every 5 seconds
        setInterval(verifyUserStatus, 5000);
    }
});

// ============================================
// GENERIC CLIENT-SIDE PAGINATION UTILITY
// ============================================
window.initClientPagination = function(options) {
    const config = Object.assign({
        container: null,
        itemSelector: '',
        footer: null,
        infoEl: null,
        paginationList: null,
        pageSizeSelect: null,
        emptyStateEl: null,
        pageSize: 10,
        itemName: 'entries',
        scrollToContainer: false,
        onPageChange: null
    }, options);

    const container = typeof config.container === 'string' ? document.querySelector(config.container) : config.container;
    const footer = typeof config.footer === 'string' ? document.querySelector(config.footer) : config.footer;

    if (!container) {
        return null;
    }

    let allItems = Array.from(container.querySelectorAll(config.itemSelector));
    let matchingItems = [...allItems];
    let currentPage = 1;
    let pageSize = (config.pageSize === 'all') ? 'all' : (parseInt(config.pageSize, 10) || 10);
    let isFiltered = false;

    // Resolve footer sub-elements
    const infoEl = config.infoEl ? (typeof config.infoEl === 'string' ? document.querySelector(config.infoEl) : config.infoEl)
        : (footer ? footer.querySelector('.pagination-info, [id$="PageInfo"], [id$="pageInfo"]') : null);

    const paginationList = config.paginationList ? (typeof config.paginationList === 'string' ? document.querySelector(config.paginationList) : config.paginationList)
        : (footer ? footer.querySelector('.pagination, [id$="PaginationList"], [id$="paginationList"]') : null);

    const pageSizeSelect = config.pageSizeSelect ? (typeof config.pageSizeSelect === 'string' ? document.querySelector(config.pageSizeSelect) : config.pageSizeSelect)
        : (footer ? footer.querySelector('select, [id$="PageSizeSelect"], [id$="pageSizeSelect"]') : null);

    const emptyStateEl = config.emptyStateEl ? (typeof config.emptyStateEl === 'string' ? document.querySelector(config.emptyStateEl) : config.emptyStateEl) : null;

    function render() {
        allItems = Array.from(container.querySelectorAll(config.itemSelector));
        const totalMatching = matchingItems.length;
        const totalAll = allItems.length;

        if (totalAll === 0) {
            if (footer) footer.style.display = 'none';
            if (emptyStateEl) emptyStateEl.style.display = '';
            return;
        }

        if (totalMatching === 0) {
            allItems.forEach(el => el.style.display = 'none');
            if (emptyStateEl) emptyStateEl.style.display = '';
            if (footer) footer.style.display = 'none';
            return;
        }

        if (emptyStateEl) emptyStateEl.style.display = 'none';
        if (footer) footer.style.display = '';

        const isAll = (pageSize === 'all');
        const effectiveSize = isAll ? Math.max(1, totalMatching) : pageSize;
        const totalPages = isAll ? 1 : Math.ceil(totalMatching / effectiveSize) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = isAll ? 0 : (currentPage - 1) * effectiveSize;
        const endIdx = isAll ? totalMatching : Math.min(startIdx + effectiveSize, totalMatching);

        // Hide all rows, display matching slice
        allItems.forEach(el => el.style.display = 'none');
        for (let i = startIdx; i < endIdx; i++) {
            if (matchingItems[i]) {
                matchingItems[i].style.display = '';
            }
        }

        // Update Info text
        if (infoEl) {
            const startDisplay = startIdx + 1;
            const endDisplay = endIdx;
            if (isFiltered && totalMatching !== totalAll) {
                infoEl.innerHTML = `Showing <strong>${startDisplay}</strong> to <strong>${endDisplay}</strong> of <strong>${totalMatching}</strong> filtered ${config.itemName} <span class="text-muted">(${totalAll} total)</span>`;
            } else {
                infoEl.innerHTML = `Showing <strong>${startDisplay}</strong> to <strong>${endDisplay}</strong> of <strong>${totalAll}</strong> ${config.itemName}`;
            }
        }

        // Render Numbered Pagination Buttons: 1, 2, 3..
        if (paginationList) {
            paginationList.className = 'pagination pagination-modern mb-0';
            let html = '';

            // Prev button
            const prevDisabled = (currentPage <= 1);
            html += `<li class="page-item ${prevDisabled ? 'disabled' : ''}">
                <button type="button" class="page-link" data-page="${currentPage - 1}" ${prevDisabled ? 'disabled tabindex="-1"' : ''} title="Previous Page">
                    <i class="bi bi-chevron-left me-1"></i> Prev
                </button>
            </li>`;

            if (totalPages <= 7) {
                // Show all numbered buttons 1, 2, 3, 4, 5, 6, 7
                for (let p = 1; p <= totalPages; p++) {
                    const isActive = (p === currentPage);
                    html += `<li class="page-item ${isActive ? 'active' : ''}">
                        <button type="button" class="page-link" data-page="${p}" ${isActive ? 'aria-current="page"' : ''}>
                            ${p}
                        </button>
                    </li>`;
                }
            } else {
                // Smart ellipsis algorithm for large number of pages
                let pages = [];
                if (currentPage <= 4) {
                    pages = [1, 2, 3, 4, 5, 'more-next', totalPages];
                } else if (currentPage >= totalPages - 3) {
                    pages = [1, 'more-prev', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
                } else {
                    pages = [1, 'more-prev', currentPage - 1, currentPage, currentPage + 1, 'more-next', totalPages];
                }

                pages.forEach(p => {
                    if (p === 'more-prev') {
                        const jumpPage = Math.max(1, currentPage - 5);
                        html += `<li class="page-item">
                            <button type="button" class="page-link page-more" data-page="${jumpPage}" title="Jump backward 5 pages">
                                …
                            </button>
                        </li>`;
                    } else if (p === 'more-next') {
                        const jumpPage = Math.min(totalPages, currentPage + 5);
                        html += `<li class="page-item">
                            <button type="button" class="page-link page-more" data-page="${jumpPage}" title="Jump forward 5 pages">
                                …
                            </button>
                        </li>`;
                    } else {
                        const isActive = (p === currentPage);
                        html += `<li class="page-item ${isActive ? 'active' : ''}">
                            <button type="button" class="page-link" data-page="${p}" ${isActive ? 'aria-current="page"' : ''}>
                                ${p}
                            </button>
                        </li>`;
                    }
                });
            }

            // Next button
            const nextDisabled = (currentPage >= totalPages);
            html += `<li class="page-item ${nextDisabled ? 'disabled' : ''}">
                <button type="button" class="page-link" data-page="${currentPage + 1}" ${nextDisabled ? 'disabled tabindex="-1"' : ''} title="Next Page">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </li>`;

            paginationList.innerHTML = html;

            // Event listener on button clicks
            paginationList.querySelectorAll('button.page-link[data-page]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.disabled) return;
                    const targetPage = parseInt(this.getAttribute('data-page'), 10);
                    if (!isNaN(targetPage) && targetPage >= 1 && targetPage <= totalPages && targetPage !== currentPage) {
                        goToPage(targetPage);
                    }
                });
            });
        }

        if (pageSizeSelect && pageSizeSelect.value !== String(pageSize)) {
            pageSizeSelect.value = String(pageSize);
        }
    }

    function goToPage(p) {
        currentPage = p;
        render();
        if (config.scrollToContainer && container) {
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (typeof config.onPageChange === 'function') {
            config.onPageChange(currentPage);
        }
    }

    function changePageSize(newSize) {
        pageSize = (newSize === 'all') ? 'all' : (parseInt(newSize, 10) || 10);
        currentPage = 1;
        render();
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function() {
            changePageSize(this.value);
        });
    }

    const controller = {
        getPage: () => currentPage,
        setPage: (p) => goToPage(p),
        getPageSize: () => pageSize,
        setPageSize: (s) => changePageSize(s),
        filter: function(predicate) {
            allItems = Array.from(container.querySelectorAll(config.itemSelector));
            if (typeof predicate === 'function') {
                matchingItems = allItems.filter(predicate);
                isFiltered = true;
            } else {
                matchingItems = [...allItems];
                isFiltered = false;
            }
            currentPage = 1;
            render();
            return matchingItems.length;
        },
        refresh: function() {
            allItems = Array.from(container.querySelectorAll(config.itemSelector));
            matchingItems = [...allItems];
            isFiltered = false;
            render();
        },
        getMatchingCount: () => matchingItems.length,
        getTotalCount: () => allItems.length
    };

    // Run initial render
    render();

    return controller;
};

