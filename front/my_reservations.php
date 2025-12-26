<?php
/**
 * My Reservations - Page for helpdesk users to view their reservations
 */

use GlpiPlugin\Reservationdetails\Entity\ReservationView;

include("../../../inc/includes.php");

Session::checkLoginUser();

// Use helpHeader for helpdesk interface, or header for admin
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader('Minhas Reservas', 'reservation');
} else {
    Html::header(
        'Minhas Reservas',
        $_SERVER['PHP_SELF'],
        'tools',
        'reservationitem'
    );
}

// Show reservations list filtered to current user
ReservationView::showReservationsList(true);

if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpFooter();
} else {
    Html::footer();
}
