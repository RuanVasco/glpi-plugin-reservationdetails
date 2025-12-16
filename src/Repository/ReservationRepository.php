<?php

namespace GlpiPlugin\Reservationdetails\Repository;

use GlpiPlugin\Reservationdetails\Entity\Reservation;
use \DB;
use \ReservationItem;

class ReservationRepository {

    public function __construct(
        private DB $db
    ) {
    }

    public function findById(int $id): ?Reservation {
        $resource = new Reservation();

        if ($resource->getFromDB($id)) {
            return $resource;
        }

        return null;
    }

    public function findStandardById(int $id): ?\Reservation {
        $reservation = new \Reservation();

        if ($reservation->getFromDB($id)) {
            return $reservation;
        }

        return null;
    }

    public function getReservationItemName(int $reservationItemId): string {
        $resItem = new ReservationItem();

        if (!$resItem->getFromDB($reservationItemId)) {
            return "";
        }

        return $this->getItemName(
            $resItem->fields['itemtype'],
            $resItem->fields['items_id']
        );
    }

    public function getItemName(string $itemtype, int $itemsId): string {
        if (!class_exists($itemtype)) {
            return '';
        }

        $table = $itemtype::getTable();

        return \Dropdown::getDropdownName($table, $itemsId) ?? '';
    }

    public function getAllActiveItems(): array {
        $item = new ReservationItem();

        return $item->find(['is_active' => 1]);
    }
}
