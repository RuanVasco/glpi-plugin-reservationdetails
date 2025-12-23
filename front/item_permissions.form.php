<?php

use GlpiPlugin\Reservationdetails\Entity\ItemPermission;

include("../../../inc/includes.php");

$plugin = new Plugin();
$obj = new ItemPermission();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    Session::addMessageAfterRedirect(__('Plugin not activated'), false, ERROR);
    Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

Session::checkLoginUser();

// Handle save action
if (isset($_POST['save']) && isset($_POST['reservationitems_id']) && Session::haveRight(ItemPermission::$rightname, UPDATE)) {
    $reservationItemId = (int)$_POST['reservationitems_id'];
    $profileIds = $_POST['profiles_ids'] ?? [];
    
    if ($reservationItemId > 0) {
        ItemPermission::saveProfilesForItem($reservationItemId, $profileIds);
        Session::addMessageAfterRedirect(__('Permissões salvas com sucesso'), true, INFO);
    }
    
    Html::redirect(ItemPermission::getSearchURL());
} else {
    // Display the form
    $withtemplate = (isset($_GET['withtemplate']) ? $_GET['withtemplate'] : "");
    $id = isset($_GET['id']) ? $_GET['id'] : -1;

    Html::header(
        ItemPermission::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'config',
        'commondropdown',
        ItemPermission::class
    );

    $obj->display([
        'id'           => $id,
        'withtemplate' => $withtemplate,
        'formoptions'  => "data-track-changes=true",
    ]);

    Html::footer();
}
