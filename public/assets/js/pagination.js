/**
 * GRM Inventory — Modern Client-Side Pagination Engine
 * Renders numbered buttons (1, 2, 3.. more like that) with Prev/Next, jump controls,
 * page size selection, and responsive layouts.
 */
(function() {
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
})();
