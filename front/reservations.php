<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Utils;
use Glpi\Application\View\TemplateRenderer;

include("../../../inc/includes.php");

$plugin = new Plugin();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    Session::addMessageAfterRedirect(__('Plugin not activated'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

Session::checkLoginUser();
Session::checkRight(Reservation::$rightname, READ);

global $DB;

$reservationRepository = new ReservationRepository($DB);

// Get open reservations by default
$reservations = $reservationRepository->getReservationsList('open');

$columns = ['Item', 'Usuário', 'Início', 'Fim'];
$values = [];

foreach ($reservations as $res) {
    $values[] = [
        'id'    => $res['id'],
        'item'  => $res['item'],
        'user'  => $res['user'],
        'begin' => Utils::formatToBr($res['begin']),
        'end'   => Utils::formatToBr($res['end'])
    ];
}

Html::header(
    __('Reservations'),
    $_SERVER['PHP_SELF'],
    'tools',
    'reservationitem'
);

$loader = new TemplateRenderer();
$loader->display('@reservationdetails/reservations_list.html.twig', [
    'columns' => $columns,
    'values'  => $values,
    'view'    => 'true'
]);

Html::footer();
