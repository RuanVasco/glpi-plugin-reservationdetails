/**
 * Reservation Details - Custom Tabs for Helpdesk Reservations Page
 * This JS is only loaded in helpdesk interface (checked via PHP)
 */
(function () {
    'use strict';

    // Only run on reservation pages
    const isReservationPage = window.location.pathname.includes('/front/reservation.php') ||
        window.location.pathname.includes('/front/reservationitem.php');

    if (!isReservationPage) {
        return;
    }

    // Define global functions
    window.loadMyReservationsWithStatus = function (status) {
        window.loadMyReservations(status, 1);
    };

    window.loadMyReservationsPage = function (page) {
        const currentStatus = window._currentReservationStatus || 'open';
        window.loadMyReservations(currentStatus, page);
    };

    window.loadMyReservations = function (status, page) {
        const container = document.getElementById('minhas-reservas-container');
        if (!container) return;

        status = status || 'open';
        page = page || 1;

        window._currentReservationStatus = status;

        const rootDoc = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';

        container.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>';

        fetch(rootDoc + '/plugins/reservationdetails/ajax/my_reservations.php?status=' + status + '&page=' + page)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar reservas: ' + error.message + '</div>';
            });
    };

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(injectReservationTabs, 300);
    });

    function injectReservationTabs() {
        // Check if tabs already exist
        if (document.getElementById('reservationdetails-tabs')) return;

        // Find the main content area
        let mainContainer = document.querySelector('main, .container-fluid, .container, #page');

        if (!mainContainer) {
            console.log('ReservationDetails: Could not find main container');
            return;
        }

        // Find a good insertion point
        let targetElement = mainContainer.querySelector('.d-flex.justify-content-center, .text-center, table, form');

        if (!targetElement) {
            targetElement = mainContainer.firstElementChild;
        }

        if (!targetElement) {
            console.log('ReservationDetails: Could not find target element');
            return;
        }

        // Create tabs
        const tabsContainer = document.createElement('div');
        tabsContainer.id = 'reservationdetails-tabs';
        tabsContainer.className = 'mb-3 mt-3';
        tabsContainer.innerHTML = `
            <ul class="nav nav-tabs justify-content-center" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-reservar" data-bs-toggle="tab" data-bs-target="#content-reservar" type="button" role="tab">
                        <i class="fas fa-plus me-1"></i> Reservar
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-minhas-reservas" data-bs-toggle="tab" data-bs-target="#content-minhas-reservas" type="button" role="tab">
                        <i class="fas fa-calendar-check me-1"></i> Minhas Reservas
                    </button>
                </li>
            </ul>
        `;

        // Create tab content wrapper
        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'tab-content';

        const reservarPane = document.createElement('div');
        reservarPane.className = 'tab-pane fade show active';
        reservarPane.id = 'content-reservar';

        const minhasReservasPane = document.createElement('div');
        minhasReservasPane.className = 'tab-pane fade';
        minhasReservasPane.id = 'content-minhas-reservas';
        minhasReservasPane.innerHTML = `
            <div id="minhas-reservas-container" class="p-3">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        `;

        contentWrapper.appendChild(reservarPane);
        contentWrapper.appendChild(minhasReservasPane);

        // Get parent and move content
        const parent = targetElement.parentNode;
        const children = Array.from(parent.children);

        // Insert tabs
        parent.insertBefore(tabsContainer, parent.firstChild);
        parent.insertBefore(contentWrapper, tabsContainer.nextSibling);

        // Move existing content to reservar pane
        children.forEach(child => {
            if (child !== tabsContainer && child !== contentWrapper) {
                reservarPane.appendChild(child);
            }
        });

        // Load reservations on tab click
        document.getElementById('tab-minhas-reservas')?.addEventListener('shown.bs.tab', function () {
            window.loadMyReservations('open', 1);
        });

        console.log('ReservationDetails: Tabs injected in helpdesk');
    }
})();
