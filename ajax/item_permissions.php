<?php

use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

include("../../../inc/includes.php");

Session::checkRight(ItemPermission::$rightname, UPDATE);

if (isset($_POST['save']) && isset($_POST['reservationitems_id'])) {
    $reservationItemId = (int)$_POST['reservationitems_id'];
    $profileIds = $_POST['profiles_ids'] ?? [];
    
    // Validate that we have a valid reservation item ID
    if ($reservationItemId <= 0) {
        Session::addMessageAfterRedirect(__('Erro: ID de item de reserva inválido'), true, ERROR);
        Html::redirect('/');
        exit;
    }
    
    ItemPermission::saveProfilesForItem($reservationItemId, $profileIds);
    
    Session::addMessageAfterRedirect(__('Permissões salvas com sucesso'), true, INFO);
    
    // Redirect back to the ReservationItem form
    Html::redirect(ReservationItem::getFormURLWithID($reservationItemId));
}

Html::redirect('/');
