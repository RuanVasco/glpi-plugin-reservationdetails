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

    public static function getTable($classname = null) {
        return 'glpi_plugin_reservationdetails_reservations';
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

        $busyIds = $resourceRepo->getOccupiedResourceIds($rawBegin, $rawEnd);
        $resources = $resourceRepo->findAvailableResources($reservationItemId, $busyIds);

        if (count($resources) <= 0) {
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
            'itemtype'          =>  $itemType
        ]);

        return true;
    }

    public static function addFieldsInReservationForm() {
        return true;
        // $loader = new TemplateRenderer();
        // $loader->display('@reservationdetails/reserve_item_form.html.twig');
    }

    public static function getRootReservation($id) {
        global $DB;

        $reservationRepository = new ReservationRepository($DB);
        $results = $reservationRepository->findStandardById($id);

        foreach ($results as $result) {
            $response = [
                'id'                    =>  $result['id'],
                'reservationitems_id'   =>  $result['reservationitems_id'],
                'begin'                 =>  $result['begin'],
                'end'                   =>  $result['end'],
                'users_id'              =>  $result['users_id'],
            ];
        }

        return $response;
    }
}
