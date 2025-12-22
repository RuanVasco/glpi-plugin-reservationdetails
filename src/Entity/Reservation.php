<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use CommonDBTM;
use Html;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Repository\ResourceRepository;
use GlpiPlugin\Reservationdetails\Utils;

class Reservation extends CommonDBTM {
    public static $rightname = 'plugin_reservationdetails_reservations';

    public static function canCreate(): bool {
        return true;
    }

    public static function canView(): bool {
        return true;
    }

    public static function getFormURL($full = true) {
        global $CFG_GLPI;

        if ($full) {
            return $CFG_GLPI['url_base'] . "/plugins/reservationdetails/front/reservation.form.php";
        }

        return $CFG_GLPI['root_doc'] . "/plugins/reservationdetails/front/reservation.form.php";
    }

    public function showForm($ID, array $options = []) {
        global $DB;

        $reservationRepo = new ReservationRepository($DB);
        $resourceRepo    = new ResourceRepository($DB);

        $idReservation = $options['idReservation'];

        $nativeReservation = $reservationRepo->findStandardById($idReservation);

        if (!$nativeReservation) {
            echo "Erro: Reserva não encontrada.";
            return false;
        }

        $rawBegin       = $nativeReservation->fields['begin'];
        $rawEnd         = $nativeReservation->fields['end'];
        $formattedBegin = Utils::formatToBr($rawBegin);

        $reservationItemId = $nativeReservation->fields['reservationitems_id'];
        $resourceName      = $reservationRepo->getReservationItemName($reservationItemId);

        $resItem = new \ReservationItem();
        $itemType = '';
        if ($resItem->getFromDB($reservationItemId)) {
            $itemType = $resItem->fields['itemtype'];
        }

        $resources = $resourceRepo->findAvailableResources($reservationItemId, $rawBegin, $rawEnd);

        // Get custom fields for this item type
        $customFields = CustomField::getFieldsForItemType($itemType);

        if (count($resources) <= 0 && count($customFields) <= 0) {
            $res = new \Reservation();
            Html::redirect($res->getFormURLWithID($nativeReservation->getID()));
        }

        echo "
            <div class='m-3 border-bottom d-flex align-items-center'>
                <h1 class='m-0'>Informações adicionais para a reserva</h1>
                <h3 class='ms-auto m-0'>" . $resourceName . ", " . $formattedBegin . "</h3>
            </div>";

        $loader = new TemplateRenderer();
        $loader->display('@reservationdetails/form_recourse_after_add.html.twig', [
            'reservationData'   =>  $nativeReservation->fields,
            'resources'         =>  $resources,
            'itemtype'          =>  $itemType,
            'customFields'      =>  $customFields
        ]);

        return true;
    }

    public static function addFieldsInReservationForm() {
        global $DB;

        // Get the reservation item ID from POST or GET
        $reservationItemId = null;
        if (isset($_POST['items']) && is_array($_POST['items']) && !empty($_POST['items'])) {
            $reservationItemId = reset($_POST['items']);
        } elseif (isset($_GET['item']) && is_array($_GET['item'])) {
            $reservationItemId = reset(array_keys($_GET['item']));
        }

        if (!$reservationItemId) {
            return true;
        }

        $resourceRepo = new ResourceRepository($DB);
        
        // Get resources linked to this reservation item (without availability check for form)
        $resources = $resourceRepo->findResourcesForItem($reservationItemId);

        if (empty($resources)) {
            return true;
        }

        $loader = new TemplateRenderer();
        $loader->display('@reservationdetails/reservation_resources_inline.html.twig', [
            'resources' => $resources
        ]);

        return true;
    }

    public static function getRootReservation($id) {
        global $DB;

        $reservationRepository = new ReservationRepository($DB);
        $result = $reservationRepository->findStandardById($id);

        if (is_null($result)) {
            return null;
        }

        $response = [
            'id'                    =>  $result['id'],
            'reservationitems_id'   =>  $result['reservationitems_id'],
            'begin'                 =>  $result['begin'],
            'end'                   =>  $result['end'],
            'users_id'              =>  $result['users_id'],
        ];

        return $response;
    }
}
