<?php

namespace GlpiPlugin\Reservationdetails\Entity;

use GlpiPlugin\Reservationdetails\Repository\ReservationRepository;
use GlpiPlugin\Reservationdetails\Repository\ResourceRepository;
use CommonDropdown;
use Session;
use Html;
use Glpi\Application\View\TemplateRenderer;

class Resource extends CommonDropdown {
    public static $rightname = 'plugin_reservationdetails_resources';

    public static function getTypeName($nb = 0) {
        return _n('Resource', 'Resources', $nb);
    }

    public static function getIcon() {
        return 'fas fa-mug-saucer';
    }

    public static function create($idResource, $idReservation) {
        global $DB;

        $resourceRepository  = new ResourceRepository($DB);
        $reservationRepository = new ReservationRepository($DB);

        $reservationData = $reservationRepository->findById($idReservation);
        $resourceData = $resourceRepository->findById($idResource);

        $iditem = $reservationData['reservationitems_id'];
        $iditemRes = $resourceData['reservationitems_id'];

        if ($iditem == $iditemRes) {
            if ($resourceRepository->isAvailable($resourceData['plugin_fillglpi_resources_id'], $reservationData['begin'], $reservationData['end'])) {
                $resourceRepository->linkResourceToReservation($idResource, $idReservation);

                $resourceTarget = $resourceRepository->findById($resourceData['plugin_fillglpi_resources_id']);

                if ($resourceTarget['ticket_entities_id']) {
                    $itemName = $reservationRepository->getReservationItemName($iditemRes);
                    $ticket = [
                        'entities_id'       =>  $resourceTarget['ticket_entities_id'],
                        'name'              =>  'Reserva para ' . $resourceTarget['name'],
                        'content'           =>  'Reserva para o ' . $resourceTarget['name'] . ' na data ' . $reservationData['begin'] . '\n' . $itemName,
                        'date'              =>  date('Y-m-d h:i:s', time()),
                        'requesttypes_id'   =>  1,
                        'status'            =>  1
                    ];

                    $track = new \Ticket();
                    //$track->check(-1, CREATE, $ticket);
                    $track->add($ticket);
                }

                return true;
            } else {
                Session::addMessageAfterRedirect(
                    __('Recurso não disponível'),
                    false,
                    ERROR
                );
            }
        }

        return false;
    }
}
