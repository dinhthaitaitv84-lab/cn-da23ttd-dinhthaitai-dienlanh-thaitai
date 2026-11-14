document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion && 'IntersectionObserver' in window) {
        var motionTargets = document.querySelectorAll([
            '.home-hero',
            '.home-statistics > *',
            '.home-section .section-heading',
            '.home-category-card',
            '.home-consult-card',
            '.home-service-card',
            '.process-step',
            '.home-commitment-card',
            '.home-review-card',
            '.home-cta',
            '.service-page > .section-heading',
            '.service-page > .service-toolbar',
            '.service-category-heading',
            '.service-card',
            '.service-cta',
            '.container > .page-heading',
            '.container > .card',
            '.admin-main > .page-heading',
            '.admin-main > .admin-heading',
            '.admin-main .dashboard-grid > .dashboard-card',
            '.admin-main .table-card',
            '.admin-main .filter-card',
            '.admin-main .report-chart-card',
            '.admin-main > .card',
            '.admin-main > section'
        ].join(','));
        var revealIndexes = new Map();
        var revealObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0, rootMargin: '0px 0px -4% 0px' });

        motionTargets.forEach(function (element) {
            var parent = element.parentElement;
            var index = revealIndexes.get(parent) || 0;
            revealIndexes.set(parent, index + 1);
            element.style.setProperty('--motion-delay', Math.min(index, 4) * 75 + 'ms');
            element.classList.add('motion-reveal');
            revealObserver.observe(element);
        });
    }

    var requestAddress = document.querySelector('#request-form #address');
    if (requestAddress) {
        var areaKeywords = requestAddress.dataset.serviceAreaKeywords.split('|');
        requestAddress.addEventListener('blur', function () {
            var address = requestAddress.value.trim().toLocaleLowerCase();
            var normalizedAddress = address.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
            var isInServiceArea = areaKeywords.some(function (keyword) {
                var lowerKeyword = keyword.toLocaleLowerCase();
                var normalizedKeyword = lowerKeyword.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
                return address.indexOf(lowerKeyword) !== -1 || normalizedAddress.indexOf(normalizedKeyword) !== -1;
            });
            if (address !== '' && !isInServiceArea) {
                window.alert('Địa chỉ nằm ngoài khu vực phục vụ. Hiện chỉ nhận yêu cầu tại ' + requestAddress.dataset.serviceAreaText + '.');
            }
        });
    }

    document.querySelectorAll('[data-history-back]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.assign(button.dataset.fallback);
        });
    });

    var accountToggle = document.querySelector('.admin-user-trigger');
    var accountDropdown = document.querySelector('.admin-user-dropdown');
    if (accountToggle && accountDropdown) {
        var closeAccountMenu = function () {
            accountDropdown.hidden = true;
            accountToggle.setAttribute('aria-expanded', 'false');
        };
        accountToggle.addEventListener('click', function () {
            var isOpen = accountToggle.getAttribute('aria-expanded') === 'true';
            accountDropdown.hidden = isOpen;
            accountToggle.setAttribute('aria-expanded', String(!isOpen));
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.admin-user-menu')) {
                closeAccountMenu();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAccountMenu();
            }
        });
    }

    var adminMenuToggle = document.querySelector('.admin-menu-toggle');
    var adminSidebar = document.querySelector('.admin-sidebar');
    var adminOverlay = document.querySelector('.admin-sidebar-overlay');
    if (adminMenuToggle && adminSidebar && adminOverlay) {
        var closeAdminMenu = function () {
            adminSidebar.classList.remove('is-open');
            adminOverlay.classList.remove('is-visible');
            adminMenuToggle.setAttribute('aria-expanded', 'false');
            adminMenuToggle.setAttribute('aria-label', 'Menu');
            adminMenuToggle.textContent = 'Menu';
            document.body.classList.remove('admin-menu-open');
        };
        var openAdminMenu = function () {
            adminSidebar.classList.add('is-open');
            adminOverlay.classList.add('is-visible');
            adminMenuToggle.setAttribute('aria-expanded', 'true');
            adminMenuToggle.setAttribute('aria-label', 'Đóng');
            adminMenuToggle.textContent = 'Đóng';
            document.body.classList.add('admin-menu-open');
        };

        adminMenuToggle.addEventListener('click', function () {
            if (adminSidebar.classList.contains('is-open')) {
                closeAdminMenu();
            } else {
                openAdminMenu();
            }
        });
        adminOverlay.addEventListener('click', closeAdminMenu);
        adminSidebar.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                closeAdminMenu();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAdminMenu();
            }
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) {
                closeAdminMenu();
            }
        });

        var currentPath = window.location.pathname.replace(/\/+$/, '');
        var sidebarLinks = Array.prototype.slice.call(adminSidebar.querySelectorAll('.admin-nav-link'));
        var matchingLink = null;
        sidebarLinks.forEach(function (link) {
            var linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '');
            if (currentPath === linkPath || currentPath.indexOf(linkPath + '/') === 0) {
                if (!matchingLink || linkPath.length > matchingLink.dataset.pathLength) {
                    matchingLink = link;
                    matchingLink.dataset.pathLength = String(linkPath.length);
                }
            }
        });
        if (!matchingLink) {
            var routeFallbacks = [
                ['/technician/detail', '/technician/jobs'],
                ['/technician/create', '/technician/index'],
                ['/technician/edit', '/technician/index'],
                ['/customer/detail', '/customer/index'],
                ['/customer/edit', '/customer/index'],
                ['/part/create', '/part/index'],
                ['/part/edit', '/part/index'],
                ['/admin/categoryCreate', '/admin/categories'],
                ['/admin/serviceCreate', '/admin/services'],
                ['/admin/serviceEdit', '/admin/services']
            ];
            routeFallbacks.some(function (route) {
                if (currentPath.indexOf(route[0]) === -1) {
                    return false;
                }
                matchingLink = sidebarLinks.find(function (link) {
                    return new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '').endsWith(route[1]);
                }) || null;
                return matchingLink !== null;
            });
        }
        if (matchingLink) {
            matchingLink.classList.add('is-active');
            matchingLink.setAttribute('aria-current', 'page');
        }
    }

    var technicianStatusPanel = document.querySelector('[data-technician-status-panel]');
    if (technicianStatusPanel) {
        var technicianStatusForm = technicianStatusPanel.querySelector('.technician-status-form');
        var technicianStatusName = technicianStatusPanel.querySelector('[data-technician-status-name]');
        var closeTechnicianStatus = technicianStatusPanel.querySelector('[data-close-technician-status]');
        var activeTechnicianStatusButton = null;

        var hideTechnicianStatus = function () {
            technicianStatusPanel.hidden = true;
            if (activeTechnicianStatusButton) {
                activeTechnicianStatusButton.setAttribute('aria-expanded', 'false');
                activeTechnicianStatusButton = null;
            }
        };

        document.querySelectorAll('[data-technician-status-trigger]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (activeTechnicianStatusButton) {
                    activeTechnicianStatusButton.setAttribute('aria-expanded', 'false');
                }
                if (activeTechnicianStatusButton === button && !technicianStatusPanel.hidden) {
                    hideTechnicianStatus();
                    return;
                }

                activeTechnicianStatusButton = button;
                activeTechnicianStatusButton.setAttribute('aria-expanded', 'true');
                technicianStatusName.textContent = button.dataset.technicianName;
                technicianStatusForm.action = technicianStatusForm.dataset.actionPrefix + encodeURIComponent(button.dataset.technicianId);
                technicianStatusForm.querySelectorAll('input[name="status"]').forEach(function (input) {
                    input.checked = input.value === button.dataset.currentStatus;
                });
                technicianStatusPanel.hidden = false;
                technicianStatusPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });

        closeTechnicianStatus.addEventListener('click', hideTechnicianStatus);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !technicianStatusPanel.hidden) {
                hideTechnicianStatus();
            }
        });
    }

    var menuToggle = document.querySelector('.nav-toggle');
    var menu = document.getElementById('primary-navigation');
    if (menuToggle && menu) {
        var closeMenu = function () {
            menu.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Menu');
            menuToggle.textContent = 'Menu';
        };
        menuToggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Đóng' : 'Menu');
            menuToggle.textContent = isOpen ? 'Đóng' : 'Menu';
        });
        menu.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        var currentPath = window.location.pathname.replace(/\/+$/, '');
        var matchingLink = null;
        menu.querySelectorAll('a[href]').forEach(function (link) {
            var linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '');
            if (currentPath === linkPath || currentPath.indexOf(linkPath + '/') === 0) {
                if (!matchingLink || linkPath.length > matchingLink.dataset.pathLength) {
                    matchingLink = link;
                    matchingLink.dataset.pathLength = String(linkPath.length);
                }
            }
        });
        if (matchingLink) {
            matchingLink.classList.add('is-active');
            matchingLink.setAttribute('aria-current', 'page');
        }
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.flash').forEach(function (flash) {
        window.setTimeout(function () {
            flash.classList.add('flash-hidden');
        }, 5000);
    });

    var imageDialog = document.querySelector('.image-dialog');
    if (imageDialog) {
        var dialogImage = imageDialog.querySelector('img');
        document.querySelectorAll('.js-image-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                dialogImage.src = button.dataset.image;
                dialogImage.alt = button.querySelector('img').alt;
                imageDialog.showModal();
            });
        });
        imageDialog.addEventListener('close', function () {
            dialogImage.removeAttribute('src');
        });
    }

    document.querySelectorAll('form[data-schedule-form]').forEach(function (form) {
        var startInput = form.querySelector('[name="start_time"]');
        var endInput = form.querySelector('[name="end_time"]');
        var duration = parseInt(form.dataset.duration, 10);
        if (!startInput || !endInput || !Number.isFinite(duration) || duration < 1) {
            return;
        }

        var updateEndTime = function () {
            if (!startInput.value) {
                endInput.min = '';
                return;
            }
            var start = new Date(startInput.value);
            if (Number.isNaN(start.getTime())) {
                return;
            }
            start.setMinutes(start.getMinutes() + duration);
            var localEnd = new Date(start.getTime() - start.getTimezoneOffset() * 60000);
            endInput.value = localEnd.toISOString().slice(0, 16);
            endInput.min = startInput.value;
            endInput.setCustomValidity('');
        };
        var validateEndTime = function () {
            endInput.setCustomValidity(
                startInput.value && endInput.value <= startInput.value
                    ? 'Thời gian kết thúc phải sau thời gian bắt đầu.'
                    : ''
            );
        };
        startInput.addEventListener('change', updateEndTime);
        endInput.addEventListener('input', validateEndTime);
        form.addEventListener('submit', validateEndTime);
    });

    document.querySelectorAll('.repair-report-form').forEach(function (form) {
        var partsList = form.querySelector('[data-parts-list]');
        var template = form.querySelector('.repair-part-template');
        var laborInput = form.querySelector('[name="labor_cost"]');
        var totalOutput = form.querySelector('[data-quote-total]');
        if (!partsList || !template || !laborInput || !totalOutput) {
            return;
        }

        var updateTotal = function () {
            var total = Number(laborInput.value) || 0;
            partsList.querySelectorAll('.repair-part-row').forEach(function (row) {
                var select = row.querySelector('.repair-part-select');
                var quantity = row.querySelector('.repair-part-qty');
                var option = select.options[select.selectedIndex];
                total += (Number(option && option.dataset.price) || 0) * (Number(quantity.value) || 0);
            });
            totalOutput.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
        };

        var addButton = form.querySelector('.js-add-part');
        if (addButton) {
            addButton.addEventListener('click', function () {
                partsList.appendChild(template.content.cloneNode(true));
                updateTotal();
            });
        }

        partsList.addEventListener('click', function (event) {
            var removeButton = event.target.closest('.js-remove-part');
            if (removeButton) {
                removeButton.closest('.repair-part-row').remove();
                updateTotal();
            }
        });

        partsList.addEventListener('change', function (event) {
            if (event.target.matches('.repair-part-select')) {
                var row = event.target.closest('.repair-part-row');
                var option = event.target.options[event.target.selectedIndex];
                var quantityInput = row.querySelector('.repair-part-qty');
                if (option.value) {
                    quantityInput.max = option.dataset.stock;
                    if (Number(quantityInput.value) > Number(option.dataset.stock)) {
                        quantityInput.value = option.dataset.stock;
                    }
                } else {
                    quantityInput.removeAttribute('max');
                }
                updateTotal();
            }
        });
        partsList.addEventListener('input', function (event) {
            if (event.target.matches('.repair-part-qty')) {
                updateTotal();
            }
        });
        laborInput.addEventListener('input', updateTotal);
        form.addEventListener('submit', function (event) {
            var quantitiesByPart = {};
            var invalid = false;
            partsList.querySelectorAll('.repair-part-row').forEach(function (row) {
                var select = row.querySelector('.repair-part-select');
                var quantity = row.querySelector('.repair-part-qty');
                var partId = select.value;
                var stock = Number(select.options[select.selectedIndex].dataset.stock);
                if (!partId) {
                    invalid = true;
                    select.setCustomValidity('Vui lòng chọn linh kiện.');
                    select.reportValidity();
                    return;
                }
                select.setCustomValidity('');
                quantitiesByPart[partId] = Math.round(((quantitiesByPart[partId] || 0) + (Number(quantity.value) || 0)) * 100) / 100;
                if (quantitiesByPart[partId] > stock) {
                    invalid = true;
                    quantity.setCustomValidity('Tổng số lượng linh kiện vượt quá tồn kho.');
                    quantity.reportValidity();
                } else {
                    quantity.setCustomValidity('');
                }
            });
            if (invalid) {
                event.preventDefault();
            }
        });
        updateTotal();
    });

    document.querySelectorAll('.star-rating').forEach(function (rating) {
        var updateRatingLabel = function () {
            var selected = rating.querySelector('input:checked');
            rating.setAttribute('aria-label', selected ? selected.value + '/5' : 'Đánh giá từ 1 đến 5');
            rating.querySelectorAll('label[data-rating]').forEach(function (label) {
                label.classList.toggle('is-selected', selected && Number(label.dataset.rating) <= Number(selected.value));
            });
            var valueOutput = rating.parentElement.querySelector('[data-rating-value]');
            if (valueOutput) {
                valueOutput.textContent = selected ? selected.value + '/5' : 'Chưa chọn';
            }
        };
        rating.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('change', updateRatingLabel);
        });
        updateRatingLabel();
    });

    var categorySelect = document.getElementById('category_id');
    var serviceSelect = document.getElementById('service_id');
    if (categorySelect && serviceSelect) {
        var selectedService = serviceSelect.dataset.selected || '';
        var filterServices = function () {
            var categoryId = categorySelect.value;
            var selectedStillAvailable = false;
            serviceSelect.disabled = categoryId === '';
            Array.prototype.forEach.call(serviceSelect.options, function (option) {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }
                var available = categoryId !== '' && option.dataset.category === categoryId;
                option.hidden = !available;
                option.disabled = !available;
                if (available && option.value === selectedService) {
                    selectedStillAvailable = true;
                }
            });
            if (!selectedStillAvailable && serviceSelect.value !== '') {
                serviceSelect.value = '';
            }
        };
        categorySelect.addEventListener('change', function () {
            selectedService = '';
            filterServices();
        });
        filterServices();
    }

    var deviceSelect = document.getElementById('device_id');
    var newDeviceFields = document.getElementById('new-device-fields');
    if (deviceSelect && newDeviceFields) {
        var updateDeviceFields = function () {
            var useSavedDevice = deviceSelect.value !== '';
            newDeviceFields.hidden = useSavedDevice;
            Array.prototype.forEach.call(newDeviceFields.querySelectorAll('input'), function (input) {
                input.disabled = useSavedDevice;
            });
        };
        deviceSelect.addEventListener('change', updateDeviceFields);
        updateDeviceFields();
    }

    var requestForm = document.getElementById('request-form');
    var preferredTime = document.getElementById('preferred_time');
    if (requestForm && preferredTime) {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        preferredTime.min = now.toISOString().slice(0, 16);
    }

    var imageInput = document.getElementById('images');
    var imagePreviews = document.getElementById('image-previews');
    if (imageInput && imagePreviews) {
        imageInput.addEventListener('change', function () {
            imagePreviews.textContent = '';
            var files = Array.prototype.slice.call(imageInput.files);
            if (files.length > 5) {
                imageInput.value = '';
                imagePreviews.textContent = 'Chỉ được chọn tối đa 5 ảnh.';
                return;
            }

            var validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            var invalidFile = files.find(function (file) {
                return validTypes.indexOf(file.type) === -1 || file.size > 2 * 1024 * 1024;
            });
            if (invalidFile) {
                imageInput.value = '';
                imagePreviews.textContent = 'Ảnh phải là JPG, PNG hoặc WEBP và tối đa 2MB mỗi ảnh.';
                return;
            }

            files.forEach(function (file) {
                var item = document.createElement('div');
                item.className = 'image-preview';
                var image = document.createElement('img');
                image.alt = file.name;
                image.src = URL.createObjectURL(file);
                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = 'Xóa';
                removeButton.addEventListener('click', function () {
                    var transfer = new DataTransfer();
                    Array.prototype.forEach.call(imageInput.files, function (currentFile, currentIndex) {
                        if (currentFile !== file) {
                            transfer.items.add(currentFile);
                        }
                    });
                    imageInput.files = transfer.files;
                    URL.revokeObjectURL(image.src);
                    item.remove();
                });
                item.appendChild(image);
                item.appendChild(removeButton);
                imagePreviews.appendChild(item);
            });
        });
    }
});
