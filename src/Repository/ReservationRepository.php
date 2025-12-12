<?php

namespace GlpiPlugin\Reservationdetails\Repository;

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use DB;
use Dropdown;

class ReservationRepository {

    public function __construct(
        private DB $db
    ) {
    }

    public function findById($id): ?Reservation {
        $resource = new Reservation();

        if ($resource->getFromDB($id)) {
            return $resource;
        }

        return null;
    }

    public function getReservationItemName(int $reservationItemId): string {
        $iterator = $this->db->request([
            'SELECT' => ['itemtype', 'items_id'],
            'FROM'   => 'glpi_reservationitems',
            'WHERE'  => ['id' => $reservationItemId]
        ]);

        if (count($iterator) === 0) {
            return "";
        }

        $data = $iterator->current();
        $table = getTableForItemType($data['itemtype']);

        return Dropdown::getDropdownName($table, $data['items_id']);
    }
}
