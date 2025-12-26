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

// Check if user has full READ right or is accessing their own data
$hasFullAccess = Session::haveRight(Reservation::$rightname, READ);
$hasUpdateRight = Session::haveRight(Reservation::$rightname, UPDATE);
$currentUserId = Session::getLoginUserID();

// Users must either have full access OR be logged in to see their own data
if (!$hasFullAccess && !$currentUserId) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

global $DB;

$reservationRepository = new ReservationRepository($DB);
$resourceRepository = new ResourceRepository($DB);

// Get reservations list by status with pagination and sorting
if (isset($_GET['byList'])) {
    $status = $_GET['byList'] === 'open' ? 'open' : 'closed';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 15;
    $sortField = isset($_GET['sortField']) ? $_GET['sortField'] : 'begin';
    $sortDir = isset($_GET['sortDir']) ? $_GET['sortDir'] : 'ASC';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Apply RLS: Users without UPDATE right see only their own reservations
    $userId = null;
    if (!$hasUpdateRight) {
        $userId = $currentUserId;
    }
    
    $reservations = $reservationRepository->getReservationsList($status, $page, $perPage, $sortField, $sortDir, $search, $userId);
    $totalCount = $reservationRepository->getReservationsCount($status, $search, $userId);
    $totalPages = max(1, ceil($totalCount / $perPage));
    
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
    
    echo json_encode([
        'data'        => $result,
        'currentPage' => $page,
        'totalPages'  => $totalPages,
        'totalCount'  => $totalCount,
        'sortField'   => $sortField,
        'sortDir'     => $sortDir
    ]);
    exit;
}

// Get single reservation details
if (isset($_GET['id'])) {
    $reservationId = (int)$_GET['id'];
    $details = $reservationRepository->getReservationDetails($reservationId);
    
    if ($details) {
        // If user doesn't have full access, check if the reservation belongs to them
        if (!$hasFullAccess) {
            // Get reservation owner
            $reservation = new \Reservation();
            if ($reservation->getFromDB($reservationId)) {
                if ($reservation->fields['users_id'] != $currentUserId) {
                    echo json_encode(['error' => 'Access denied']);
                    exit;
                }
            }
        }
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'Reservation not found']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
