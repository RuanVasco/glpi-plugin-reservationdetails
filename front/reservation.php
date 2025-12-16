<?php
include('../../../inc/includes.php');

Session::checkRight(GlpiPlugin\Reservationdetails\Entity\Reservation::$rightname, READ);

Html::header(
    GlpiPlugin\Reservationdetails\Entity\Reservation::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'tools',
    \Reservation::class
);

Search::show(GlpiPlugin\Reservationdetails\Entity\Reservation::class);

Html::footer();
