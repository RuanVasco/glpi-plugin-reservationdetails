<?php

use GlpiPlugin\Reservationdetails\Entity\Resource;
use GlpiPlugin\Reservationdetails\Repository\ResourceRepository;
use Glpi\Event;

include("../../../inc/includes.php");

$plugin = new Plugin();
$obj = new Resource();

if (!$plugin->isInstalled('reservationdetails') || !$plugin->isActivated('reservationdetails')) {
    Session::addMessageAfterRedirect(__('Plugin not activated'), false, ERROR);

    Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

Session::checkLoginUser();

global $DB;
if (isset($_POST['add'])) {
    if (!isset($_POST['open_ticket'])) {
        unset($_POST['ticket_entities_id']);
    }

    if (!isset($_POST['include_quantity'])) {
        unset($_POST['stock']);
    }

    $obj->check(-1, CREATE, $_POST);
    $newID = $obj->add($_POST);

    if ($newID) {
        $resourceRepo = new ResourceRepository($DB);
        $resourceRepo->linkReservationItems($newID, $_POST);
    }

    Html::redirect($obj->getLinkURL());
} else if (isset($_POST["update"])) {

    if (!isset($_POST['open_ticket'])) {
        $_POST['ticket_entities_id'] = NULL;
    }

    if (!isset($_POST['include_quantity'])) {
        $_POST['stock'] = NULL;
    }

    $obj->check($_POST['id'], UPDATE, $_POST);
    $obj->update($_POST);

    $resourceRepo = new ResourceRepository($DB);
    $resourceRepo->syncReservationItems($_POST['id'], $_POST);

    Html::redirect($obj->getLinkURL());
} else if (isset($_POST['delete'])) {
    $obj->check($_POST["id"], PURGE);

    if ($obj->delete($_POST)) {
        Event::log(
            $_POST["id"],
            Resource::class,
            4,
            "",
            sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
        );
    }

    $DB->delete('glpi_plugin_reservationdetails_resources_reservationsitems', [
        'plugin_reservationdetails_resources_id' => $_POST['id']
    ]);

    $obj->redirectToList();
} else {
    $withtemplate = (isset($_GET['withtemplate']) ? $_GET['withtemplate'] : "");
    $id = isset($_GET['id']) ? $_GET['id'] : -1;

    Html::header(
        Resource::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'config',
        'commondropdown',
        Resource::class
    );

    $obj->display([
        'id'           => $id,
        'withtemplate' => $withtemplate,
        'formoptions'  => "data-track-changes=true",
    ]);

    Html::footer();
}
