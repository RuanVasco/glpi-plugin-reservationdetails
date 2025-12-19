<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Repository\ResourceRepository;
use GlpiPlugin\Reservationdetails\Utils;

include("../../../inc/includes.php");

header('Content-Type: application/json');

$plugin = new Plugin();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    echo json_encode(['error' => 'Plugin not activated']);
    exit;
}

Session::checkLoginUser();

if (!Session::haveRight(Reservation::$rightname, READ)) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

global $DB;

$reservationRepository = new ReservationRepository($DB);
$resourceRepository = new ResourceRepository($DB);

// Get reservations list by status
if (isset($_GET['byList'])) {
    $status = $_GET['byList'] === 'open' ? 'open' : 'closed';
    $reservations = $reservationRepository->getReservationsList($status);
    
    $result = [];
    foreach ($reservations as $res) {
        $result[] = [
            'id'    => $res['id'],
            'item'  => $res['item'],
            'user'  => $res['user'],
            'begin' => Utils::formatToBr($res['begin']),
            'end'   => Utils::formatToBr($res['end'])
        ];
    }
    
    echo json_encode($result);
    exit;
}

// Get single reservation details
if (isset($_GET['id'])) {
    $reservationId = (int)$_GET['id'];
    $details = $reservationRepository->getReservationDetails($reservationId);
    
    if ($details) {
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'Reservation not found']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
