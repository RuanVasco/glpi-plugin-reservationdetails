<?php

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Entity\CustomFieldValue;

include("../../../inc/includes.php");

$plugin = new Plugin();
$obj = new Reservation();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    Session::addMessageAfterRedirect(__('Plugin not activated'), false, ERROR);

    Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

Session::checkLoginUser();

if (isset($_POST['add'])) {
    $_POST['reservations_id'] = $_GET['id'];

    $obj->check(-1, CREATE, $_POST);
    $obj->add($_POST);

    // Process resources
    foreach ($_POST as $i => $key) {
        if (strpos($i, 'resource_id_') !== false) {
            if (!Resource::create($key, $_POST['reservations_id'])) {
                Session::addMessageAfterRedirect(
                    __('Resource not avaible for this date'),
                    false,
                    ERROR
                );
            }
        }
    }

    // Save custom field values
    $customFieldValues = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'customfield_') === 0) {
            $fieldId = (int) str_replace('customfield_', '', $key);
            if ($fieldId > 0 && !empty($value)) {
                $customFieldValues[$fieldId] = $value;
            }
        }
    }
    
    if (!empty($customFieldValues)) {
        CustomFieldValue::saveForReservation($_GET['id'], $customFieldValues);
    }

    $ri = new \ReservationItem();
    $ri->redirectToList();
} else if (isset($_POST["update"])) {
    Html::redirect($obj->getLinkURL());
} else {
    $withtemplate = isset($_GET['withtemplate']) ? $_GET['withtemplate'] : "";
    $id = -1;
    Reservation::displayFullPageForItem($id, null, [
        'idReservation' => $_GET['id']
    ]);
}
