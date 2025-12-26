/**
 * Reservation Details - Add "Minhas Reservas" button to helpdesk reservations page
 */
(function () {
    'use strict';

    // Only run on reservation pages
    const isReservationPage = window.location.pathname.includes('/front/reservation.php') ||
        window.location.pathname.includes('/front/reservationitem.php');

    if (!isReservationPage) {
        return;
    }

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(addMyReservationsButton, 300);
    });

    function addMyReservationsButton() {
        // Check if button already exists
        if (document.getElementById('btn-minhas-reservas')) return;

        // Find the button area - look for "Ver calendário" button
        const calendarBtn = document.querySelector('a[href*="reservation.php?reservationitems_id=0"]');
        const searchBtn = document.querySelector('#makesearch');

        let targetContainer = null;

        if (searchBtn) {
            targetContainer = searchBtn;
        } else if (calendarBtn) {
            targetContainer = calendarBtn.parentElement;
        }

        if (!targetContainer) {
            console.log('ReservationDetails: Could not find target container for button');
            return;
        }

        // Get root doc from GLPI config
        const rootDoc = typeof CFG_GLPI !== 'undefined' ? CFG_GLPI.root_doc : '';

        // Create the button
        const btn = document.createElement('a');
        btn.id = 'btn-minhas-reservas';
        btn.href = rootDoc + '/plugins/reservationdetails/front/my_reservations.php';
        btn.className = 'btn btn-secondary ms-2';
        btn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Minhas Reservas';
        btn.style.marginLeft = '10px';

        // Insert after the existing buttons
        if (searchBtn) {
            // Insert at the end of the button area
            searchBtn.appendChild(document.createElement('br'));
            searchBtn.appendChild(document.createElement('br'));
            searchBtn.appendChild(btn);
        } else if (targetContainer) {
            targetContainer.appendChild(btn);
        }

        console.log('ReservationDetails: My Reservations button added');
    }
})();
